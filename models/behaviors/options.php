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
	public function beforeFind(&$Model, $query = array()) {
        $optionName = $this->settings['optionName'];
        $query = $this->_mergeDefaultOption($Model, $query);
		if (isset($query[$optionName])) {
			$options = $query[$optionName];
			unset($query[$optionName]);
			$query = Set::merge($this->defaultQuery, $this->options($Model, $options), Set::filter($query));
		}
		return $query;
	}

    /**
     * $options[$type]のqueryを返却
     */
	public function options(&$Model, $type = null){
		$args = func_get_args();
		if (func_num_args() > 2) {
			array_shift($args);
			$type = $args;
		}
        $type = Set::normalize($type);

		$option = array();
        foreach($type as $t => $arg){
            $option = Set::merge($option, $this->_createOption($Model, $t, $arg));
        }
		return $option;
	}

    /**
     * 対象のoptionを作成して取得
     */
    private function _createOption($Model, $type, $arg=null){
		$optionName = $this->settings['optionName'];
		$option = $this->_getOption($Model, $type, $arg);
		$base = array();
		if ($Model->baseOption) {
			$base = $this->_getBase($Model->baseOption, $Model->{$optionName});
		}
		$options = array();
		if (isset($option[$optionName]) && !empty($option[$optionName])) {
			$options = $this->_intelligentlyMerge(array(), $option[$optionName], $Model->{$optionName});
			unset($option[$optionName]);
		}
		return Set::merge($base, $options, $option);
    }

    /**
     * 対象のoptionを取得。functionだったら実行結果取得
     */
    private function _getOption($Model, $type, $arg=null){
		$optionName = $this->settings['optionName'];
        if(strpos($type, 'function:') === 0){
            $option = $this->_execLambdaOption($Model, $type, $arg);
        }else{
            $option = isset($Model->{$optionName}[$type]) ? $Model->{$optionName}[$type] : array();
        }
        return $option;
    }

    /**
     * functionなoptionを実行して取得
     */
    private function _execLambdaOption($Model, $type, $arg=null){
		$optionName = $this->settings['optionName'];
        list($prefix, $type) = explode(':', $type, 2);
        if(empty($type)) return array();
        if(empty($Model->{$optionName}[$type])) return array();
        $func = $Model->{$optionName}[$type];
        return call_user_func_array($func, (array) $arg);
    }

    /**
     * 基になるoptionを取得
     */
	private function _getBase($baseOption, $options) {
		$base = array();
		if ($baseOption === true && !empty($options['base'])) {
			$base = $options['base'];
		} elseif (is_array($baseOption)) {
			$base = $this->_intelligentlyMerge($base, $baseOption, $options);
		} elseif (!empty($options[$baseOption])) {
			$base = $this->_intelligentlyMerge($base, $options[$baseOption], $options);
		}
		return $base;
	}

    /**
     * defaultのoptionをqueryにマージ
     */
    private function _mergeDefaultOption($Model, $query){
        if(!empty($Model->defaultOption)){
            $defaultOption = $Model->defaultOption;
            $optionName = $this->settings['optionName'];
            $options = isset($query[$optionName]) ? $query[$optionName] : array();
            if($defaultOption === true){
                $defaultOption = 'default';
            }
            $query[$optionName] = Set::merge($options, $defaultOption);
        }
        return $query;
    }

    /**
     * マージ(?)
     */
	private function _intelligentlyMerge($data, $merges, $options) {
		$merges = (array)$merges;
		if (Set::numeric(array_keys($merges))) {
			foreach($merges as $merge) {
				if (!empty($options[$merge])) {
					$data = $this->_intelligentlyMerge($data, $options[$merge], $options);
				}
			}
		} else {
			$optionName = $this->settings['optionName'];
			if (array_key_exists($optionName, $merges)) {
				$data = $this->_intelligentlyMerge($data, $merges[$optionName], $options);
				unset($merges[$optionName]);
			}
			$data = Set::merge($data, $merges);
		}
		return $data;
	}
}