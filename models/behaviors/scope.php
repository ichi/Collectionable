<?php
/*
 * Scope
 * #OptionsBehavior必須
 */
class ScopeBehavior extends ModelBehavior
{

    private $scopes = array();
    public $settings = array(
        'findMethods' => array('all', 'first', 'count'),
        'paramNames' => array('contain', 'conditions', 'order', 'limit', 'group', 'page', 'offset', 'joins'),
    );

    /**
     * setup
     * 
     * @param Object $Model
     * @param array $settings
     */
    function setup(&$Model, $settings=array())
    {
        $this->settings = array_merge($this->settings, (array) $settings);
    }
    
    /**
     * create
     * @param string $name
     */
    public function scope(&$Model){
        return new FindScope($Model, $this->settings);
    }
}

class FindScope extends Overloadable{
    
    private $settings = array();
    private $params = array();
    private $model;
    
    function __construct(&$Model, $settings=array()){
        $this->model =& $Model;
        $this->settings = array_merge($this->settings, $settings);
    }
    
    /**
     * end
     * @param string $name
     */
    public function end($type='all'){
        return $this->model->find($type, $this->params);
    }
    
    /**
     * get
     */
    public function get(){
        return $this->params;
    }
    
    /**
     * options
     * @param array $options
     */
    public function options($options){
        $params = $this->model->options($options);
        $this->mergeParams($params);
        return $this;
    }
    
    /**
     * params
     * @param array $params
     */
    public function params($params){
        $this->mergeParams($params);
        return $this;
    }
    
    
    /**
     * magic method
     * @param string $name
     */
    public function call__($method, $args){
        if(in_array($method, $this->settings['findMethods'])){
            return $this->end($method);
        }
        $params = array();
        if(in_array($method, $this->settings['paramNames'])){
            $arg = !empty($args[0]) ? $args[0] : '';
            $params = array($method=>$arg);
        }else{
            if(!empty($args)){
                $params = $this->model->options(array("function:{$method}"=>$args));
            }else{
                $params = $this->model->options($method);
            }
        }
        $this->mergeParams($params);
        return $this;
    }
    
    
    /**
     * merge params
     * @param array $params
     */
    private function mergeParams($params){
        $this->params = Set::merge(array(), $params, Set::filter($this->params));
    }
}