<?php


namespace Itonomy\ModificationDetector\Model\Config\Reader;
/**
 *
 */

class Dom extends \Magento\Framework\ObjectManager\Config\Reader\Dom
{
    /**
     * @return array
     *
     */
    public function getPreferences()
    {
        $conflicts = [];

        foreach (['global', 'adminhtml', 'frontend'] as $scope) {
            $fileList = $this->_fileResolver->get($this->_fileName, $scope);
            if (count($fileList)) {
                foreach ($fileList as $key => $content) {
                    $dom = new \DOMDocument();
                    $res = $dom->loadXML($content);
                    if ($res) {
                        foreach ($dom->getElementsByTagName('preference') as $preference) {
                            $for = $preference->getAttribute('for');
                            $type = $preference->getAttribute('type');
                            if ($for && $type) {
                                if (!isset($conflicts[$for])) {
                                    $conflicts[$for] = [
                                        'classes' => []
                                    ];
                                }

                                if (!in_array($type, $conflicts[$for]['classes'])) {
                                    $conflicts[$for]['classes'][] = $type;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($conflicts as $origClass => $item) {
            $hasNoMagentoClasses = false;
            foreach ($item['classes'] as $class) {
                if (strpos($class, 'Magento\\') !== 0 && strpos($class, '\\Magento\\') !== 0) {
                    $hasNoMagentoClasses = true;
                }
            }

            if (!$hasNoMagentoClasses) {
                unset($conflicts[$origClass]);
            }

            if (strpos($origClass, 'Interface') !== false && count($item['classes']) < 2) {
                unset($conflicts[$origClass]);
            }
        }

        return $conflicts;
    }

    /**
     * @return array
     *
     */
    public function getPlugins()
    {
        $conflicts = [];

        foreach (['global', 'adminhtml', 'frontend'] as $scope) {
            $fileList = $this->_fileResolver->get($this->_fileName, $scope);
            if (count($fileList)) {
                foreach ($fileList as $key => $content) {
                    $dom = new \DOMDocument();
                    $res = $dom->loadXML($content);
                    if ($res) {
                        foreach ($dom->getElementsByTagName('plugin') as $plugin) {
                            /** @var \DOMElement $plugin */
                            $for = $plugin->parentNode->getAttribute('name');
                            $type = $plugin->getAttribute('type');
                            if ($for && $type) {
                                if (!isset($conflicts[$for])) {
                                    $conflicts[$for] = [
                                        'classes' => []
                                    ];
                                }

                                if (!in_array($type, $conflicts[$for]['classes'])) {
                                    $conflicts[$for]['classes'][] = $type;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($conflicts as $origClass => $item) {
            $hasNoMagentoClasses = false;
            foreach ($item['classes'] as $class) {
                if (strpos($class, 'Magento\\') !== 0 && strpos($class, '\\Magento\\') !== 0) {
                    $hasNoMagentoClasses = true;
                }
            }

            if (!$hasNoMagentoClasses) {
                unset($conflicts[$origClass]);
            }

            if (strpos($origClass, 'Interface') !== false && count($item['classes']) < 2) {
                unset($conflicts[$origClass]);
            }
        }

        return $conflicts;
    }
}
