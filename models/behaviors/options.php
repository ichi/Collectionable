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

		$option = array();
		if (is_array($type)) {
			foreach ($type as $t) {
				$option = Set::merge($option, $this->options($Model, $t));
			}
		} else {
			$optionName = $this->settings['optionName'];
			$option = isset($Model->{$optionName}[$type]) ? $Model->{$optionName}[$type] : array();
			$base = array();
			if ($Model->baseOption) {
				$base = $this->_getBase($Model->baseOption, $Model->{$optionName});
			}
			$options = array();
			if (isset($option[$optionName]) && !empty($option[$optionName])) {
				$options = $this->_intelligentlyMerge(array(), $option[$optionName], $Model->{$optionName});
				unset($option[$optionName]);
			}
			$option = Set::merge($base, $options, $option);
		}
		return $option;
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