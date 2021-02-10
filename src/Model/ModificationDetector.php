<?php


namespace Itonomy\ModificationDetector\Model;


use Itonomy\ModificationDetector\Model\Config\Reader\Dom;
use Magento\Framework\Interception\Code\InterfaceValidator;

class ModificationDetector
{
    /**
     * @var Dom
     */
    protected $dom;
    /**
     * @var bool
     */
    protected $hideNatives = false;

    public function __construct(
        Dom $dom
    ){
        $this->dom = $dom;
    }

    /**
     * @return array
     */
    public function getPlugins(){

        $pluginList = $this->dom->getPlugins();

        if($this->hideNatives==true) {
            $this->filterNatives($pluginList);
        }

        return $pluginList;
    }
    /**
     * @return array
     */
    public function getPreferences(){
        $preferenceList = $this->dom->getPreferences();

        if($this->hideNatives==true) {
            $this->filterNatives($preferenceList);
        }

        return $preferenceList;
    }
    /**
     * @param string $classname
     * @return array|false
     */
    public function getPluginByClassname($classname){

        $pluginList = $this->getPlugins();

        if($this->hideNatives==true){
            $this->filterNatives($pluginList);
        }

        $this->filterByClassname($pluginList, $classname);

        return $pluginList;

    }
    /**
     * @param string $classname
     * @return array|false
     */
    public function getPreferenceByClassname($classname){

        $preferenceList = $this->getPreferences();

        if($this->hideNatives==true){
            $this->filterNatives($preferenceList);
        }
        $this->filterByClassname($preferenceList, $classname);
        return $preferenceList;
    }

    public function setHideNatives($bool){
        $this->hideNatives = (bool)$bool;
    }

    /**
     * @param $array
     * @param $keyword
     * @param false $mode false=CONTAINS|true=NOT CONTAIN
     * @return void
     */
    protected function filterByClassname(&$array, $keyword, $mode=false){

        if(strlen($keyword) < 3) {
            return;
        }

        foreach ($array as $class => $items){
            foreach ($items['classes'] as $key => $item) {
                if($mode == false){
                    if (stripos($item, $keyword) === false) {
                        unset($array[$class]['classes'][$key]);
                    }
                } else {
                    if (stripos($item, $keyword) !== false) {
                        unset($array[$class]['classes'][$key]);
                    }
                }
                if(count($array[$class]['classes']) === 0){
                    unset($array[$class]);
                }
            }
        }
    }

    /**
     * @param $list
     * @return array|false
     */
    protected function filterNatives(&$array){
        $this->filterByClassname($array, 'Magento\\',true);
    }
    /**
     * @param bool $hideNatives
     * @throws \ReflectionException
     */
    public function findPluginConflicts(){

        $pluginList = $this->getPlugins();
        $pluginMethodList = [];

        foreach ($pluginList as $classname => $plugins){
            foreach ($plugins['classes'] as $pluginClassname){

                $class = new \ReflectionClass($pluginClassname);

                foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){

                    $methodName = $this->getOriginMethodName($method->getName());
                    $methodType = $this->getMethodType($method->getName());

                    if($this->hideNatives == true) {
                        if(preg_match('/Magento\\.*/',$pluginClassname)) continue;
                    }

                    if($methodType != '') {
                        $pluginMethodList[$classname][$methodName]['plugins'][] = [
                            'method' => $method->getName(),
                            'type' => $methodType,
                            'class' => $pluginClassname,
                            'no_callable' => false
                        ];

                        $key = array_key_last($pluginMethodList[$classname][$methodName]['plugins']);

                        if($methodType ==  InterfaceValidator::METHOD_AROUND){
                            if($this->doesPluginCallCallable($pluginClassname, $method->getName()) !== true){
                                $pluginMethodList[$classname][$methodName]['plugins'][$key]['no_callable'] = true;
                            };
                        }
                        if(count($pluginMethodList[$classname][$methodName]['plugins'])>1){
                            $pluginMethodList[$classname][$methodName]['potential_conflict'] = true;
                        } else {
                            $pluginMethodList[$classname][$methodName]['potential_conflict'] = false;
                        }
                    }
                }
            }
        }

        return $pluginMethodList;
    }

    /**
     * @param $pluginMethodName
     * @return string|null
     */
    protected function getMethodType($pluginMethodName)
    {
        if (0 === strpos($pluginMethodName, InterfaceValidator::METHOD_AFTER)) {
            return InterfaceValidator::METHOD_AFTER;
        }
        if (0 === strpos($pluginMethodName, InterfaceValidator::METHOD_BEFORE)) {
            return InterfaceValidator::METHOD_BEFORE;
        }
        if (0 === strpos($pluginMethodName, InterfaceValidator::METHOD_AROUND)) {
            return InterfaceValidator::METHOD_AROUND;
        }

        return null;
    }

    /**
     * @param $pluginMethodName
     * @return string|null
     */
    protected function getOriginMethodName($pluginMethodName)
    {
        $methodType = $this->getMethodType($pluginMethodName);

        if (InterfaceValidator::METHOD_AFTER === $methodType) {
            return lcfirst(substr($pluginMethodName, 5));
        }
        if (InterfaceValidator::METHOD_BEFORE === $methodType || InterfaceValidator::METHOD_AROUND === $methodType) {
            return lcfirst(substr($pluginMethodName, 6));
        }

        return null;
    }

    /**
     * @param $class
     * @param $method
     * @return bool
     */
    protected function doesPluginCallCallable($class, $method){

        try {
            $func = new \ReflectionMethod($class, $method);
            $f = $func->getFileName();
            $start_line = $func->getStartLine() - 1;
            $end_line = $func->getEndLine();

            $source = file($f);
            $source = implode('', array_slice($source, 0, count($source)));
            $source = preg_split("/" . PHP_EOL . "/", $source);

            $body = '';
            //$start_line contains function ($params) so we must begin on the line below
            for ($i = $start_line+1; $i < $end_line; $i++)
                $body .= "{$source[$i]}\n";

            $functionParams = $func->getParameters();
            $proceedParamPattern = '/'.$functionParams[1]->getName().'/i';
            if (preg_match($proceedParamPattern, $body)) {
                    return true;
            } else {
                return false;
            }
        } catch (\ReflectionException $exception){
            return false;
        }
    }
}
