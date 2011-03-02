<?php

class OptionsBehavior extends ModelBehavior {
	
	public $settings = array();
	private $defaultSettings = array(
		'setupProperty' => true,
		'baseOption' => false,
		'defaultOption' => false,
		'optionName' => 'options',
	);

	private $defaultQuery = array(
		'conditions' => null, 'fields' => null, 'joins' => array(), 'limit' => null,
		'offset' => null, 'order' => null, 'page' => null, 'group' => null, 'callbacks' => true
	);

    private $Model;
    private $options;
    private $optionName;

    /**
     * setup
     */
	public function setup(&$Model, $settings = array()) {
		$this->settings = array_merge($this->defaultSettings, (array)$settings);
		$optionName = $this->settings['optionName'];
		if ($this->settings['setupProperty']) {
            if(method_exists($Model, 'setOptions')){
                $Model->{$optionName} = $Model->setOptions();
            }
			if (empty($Model->{$optionName})) {
				$Model->{$optionName} = array();
			}
			if (empty($Model->baseOption)) {
				$Model->baseOption = $this->settings['baseOption'];
			}
		}
		return true;
	}

    /**
     * beforeFind
     */
	public function beforeFind(&$Model, $queryParams = array()) {
        $this->_setThisOptions($Model);
        $optionName = $this->optionName;
		if (isset($queryParams[$optionName])) {
			$options = $queryParams[$optionName];
			unset($queryParams[$optionName]);
			$queryParams = Set::merge($this->defaultQuery, $this->options($Model, $options), Set::filter($queryParams));
		}
        $queryParams = $this->_mergeDefaultParams($queryParams);
		return $queryParams;
	}

    /**
     * $optionsに追加
     */
    public function addOption(&$Model, $key, $param){
        $optionName = $this->settings['optionName'];
        if(empty($Model->{$optionName})) $Model->{$optionName} = array();
        $Model->{$optionName}[$key] = $param;
    }

    /**
     * $optionsからparamsを返却
     */
	public function options(&$Model, $options = null){
        $this->_setThisOptions($Model);
		$args = func_get_args();
		if (func_num_args() > 2) {
			array_shift($args);
			$options = $args;
		}
		
        $params = $this->_getParamsByOptions($options);
        $params = $this->_mergeBaseParams($params);
        
        return $params;
	}

    /*
     * このインスタンスにいろいろくっつけとく
     * @param object $Model
     * @param mixed $names
     * @return array
     */
    private function _setThisOptions(&$Model){
        $this->Model =& $Model;
        $this->optionName = $this->settings['optionName'];
        $options = array();
        if(!empty($Model->{$this->optionName})) $options = $Model->{$this->optionName};
        $this->options = $options;
    }

    /*
     * baseOptionとマージしたparamsを返却
     * @param array $params
     * @return array
     */
    private function _mergeBaseParams($params){
        $base = false;
        if (!empty($this->Model->baseOption)) {
            $base = $this->Model->baseOption;
        }elseif(!empty($this->options['base'])){
            $base = $this->options['base'];
        }
        if(empty($base)) return $params;
        $base = $this->_convertToParams($base);
        return Set::merge($base, $params);
    }

    /*
     * defaultOptionとマージしたparamsを返却
     * @param array $params
     * @return array
     */
    private function _mergeDefaultParams($params){
        $default = false;
        if (!empty($this->Model->defaultOption)) {
            $default = $this->Model->defaultOption;
        }elseif(!empty($this->options['default'])){
            $default = $this->options['default'];
        }
        if(empty($default)) return $params;
        $default = $this->_convertToParams($default);
        return Set::merge($default, $params);
    }

    
    /*
     * paramsなのかoptionsなのかチェックしてparamsにして返却
     * @param mixed $options
     * @return array
     */
    private function _convertToParams($options){
        $optionName = $this->optionName;
        if($this->_isValidOptions($options)){
            return $this->_getParamsByOptions($options);
        }else{
            return $this->_recurseParams($options);
        }
    }

    /*
     * paramsを再帰的にチェックして必要ならマージして返却
     * @param array $param
     * @return array
     */
    private function _recurseParams($params){
        $optionName = $this->optionName;
        if(array_key_exists($optionName, $params)){
            $params = Set::merge($params, $this->_getParamsByOptions($params[$optionName]));
        }
        return $params;
    }

    /*
     * options配列（ ex: 'hoge' / array('hoge', 'fuga', 'func'=>$arg) ）からparamsを取得して返却
     * @param mixed(string | array) $options
     * @return array
     */
    private function _getParamsByOptions($options){
        if(!$this->_isValidOptions($options)){
            trigger_error('collectable.Options : options is not valid.', E_USER_ERROR);
        }
        $optionName = $this->optionName;
        $params = array();
        $options = Set::normalize($options);
        foreach($options as $key => $args){
            $param = array();
            if(isset($args)){
                $param = $this->_getLambdaParam($key, $args);
            }else{
                $param = $this->_getParam($key);
            }
            $param = $this->_recurseParams($param);
            $params = Set::merge($params, $param);
        }
        return $params;
    }

    /*
     * 指定optionのparam返却
     * @param string $key
     * @return array
     */
    private function _getParam($key){
        if(empty($this->options[$key])) return array();
        return $this->options[$key];
    }

    /*
     * 関数型のoptionを実行してのparam返却
     * @param string $key
     * @param array $args
     * @return array
     */
    private function _getLambdaParam($key, $args=array()){
        $method = $this->_getParam($key);
        return (array) call_user_func_array($method, (array) $args);
    }

    /*
     * optionsとして正しい？
     * @param mixed $options
     * @return boolean
     */
    private function _isValidOptions($options){
        if(is_string($options)) return true;
        if($this->_isNonHashArray($options)) return true;
        try{
            $options = Set::normalize($options);
            if(array_intersect_key($options, $this->options) == $options) return true;
        }catch(Exception $e){
            //nothing
        }
        return false;
    }

    /*
     * ハッシュでない配列か？
     * @param array $array
     * @return boolean
     */
    private function _isNonHashArray($array){
        return Set::numeric(array_keys($array));
    }
    
}