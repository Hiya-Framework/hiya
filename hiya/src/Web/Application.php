<?php
/**
 * Hiya Framework
 * 
 * @copyright (c) 2026 TaktikSpace.com
 * @link www.taktikspace.com/hiya
 * @license BSD-3-Clause
 */

namespace Hiya\Web;

class Application extends \CWebApplication
{
    public function init()
    {
        // By default Hiya application will use App as base namespace
        \Yii::setPathOfAlias('App', $this->getBasePath());
        parent::init();
    }

    public function createController($route, $owner = null)
    {
        if ($owner === null) {
            $owner = $this;
        }
        if ((array)$route === $route || ($route = trim($route, '/')) === '') {
            $route = $owner->defaultController;
        }

        $caseSensitive = $this->getUrlManager()->caseSensitive;

        $route .= '/';
        while (($pos = strpos($route, '/')) !== false) {
            $id = substr($route, 0, $pos);
            if (!preg_match('/^\w+$/', $id)) {
                return null;
            }
            if (!$caseSensitive) {
                $id = strtolower($id);
            }
            $route = (string)substr($route, $pos + 1);

            if (!isset($basePath)) { // first segment
                if (isset($owner->controllerMap[$id])) {
                    return [
                        \Yii::createComponent($owner->controllerMap[$id], $id, $owner === $this ? null : $owner),
                        $this->parseActionParams($route),
                    ];
                }

                if (($module = $owner->getModule($id)) !== null) {
                    return $this->createController($route, $module);
                }

                $basePath = $owner->getControllerPath();
                $controllerID = '';
            } else {
                $controllerID .= '/';
            }

            $plainClassName = ucfirst($id) . 'Controller';
            $classFile = $basePath . DIRECTORY_SEPARATOR . $plainClassName . '.php';

            // bentuk nama class dengan namespace kalau ada
            $nsClassName = $owner->controllerNamespace !== null
                ? rtrim($owner->controllerNamespace, '\\').'\\'.ucfirst($id).'Controller'
                : null;

            if (is_file($classFile)) {
                require_once $classFile;

                // cek versi namespace dulu
                if ($nsClassName !== null && class_exists($nsClassName, false) && is_subclass_of($nsClassName, 'CController')) {
                    $id[0] = strtolower($id[0]);
                    return [
                        new $nsClassName($controllerID.$id, $owner===$this?null:$owner),
                        $this->parseActionParams($route),
                    ];
                }

                // fallback ke non-namespace hanya jika controllerNamespace null
                if ($nsClassName === null && class_exists($plainClassName, false) && is_subclass_of($plainClassName, 'CController')) {
                    $id[0] = strtolower($id[0]);
                    return [
                        new $plainClassName($controllerID.$id, $owner===$this?null:$owner),
                        $this->parseActionParams($route),
                    ];
                }

                return null;
            }

            $controllerID .= $id;
            $basePath .= DIRECTORY_SEPARATOR . $id;
        }
    }

}