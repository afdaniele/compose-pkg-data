<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\classes\Core;
use \system\packages\data\Data;


function execute(&$service, &$actionName, &$arguments) {
    $action = $service['actions'][$actionName];
    Core::startSession();
    //
    switch ($actionName) {
        case 'new':
            // get arguments
            $database_name = $arguments['database'];
            // ---
            $res = Data::new($database_name);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        case 'drop':
            // get arguments
            $database_name = $arguments['database'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name, true);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            $res = Data::drop($database_name);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        case 'list':
            // get arguments
            $database_name = $arguments['database'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            $res = Data::list($database_name);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK(['keys' => $res['data']]);
            break;
        //
        case 'exists':
            // get arguments
            $database_name = $arguments['database'];
            $key = $arguments['key'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            // check existence
            $exists = Data::has($database_name, $key);
            return response200OK(['exists' => $exists]);
            break;
        //
        case 'get':
            // get arguments
            $database_name = $arguments['database'];
            $key = $arguments['key'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            // fetch data
            $res = Data::get($database_name, $key);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // data is here, apply seek if present
            $data = $res['data'];
            if (array_key_exists('seek', $arguments)) {
                $path = explode('/', trim($arguments['seek'], '/'));
                foreach ($path as $p) {
                    if (!is_assoc($data) && startsWith($p, '[') && endsWith($p, ']')) {
                        // this is a selector of type: /[a,b,c]/
                        $ks = explode(',', trim($p, "[]"));
                        $data = array_values(array_map(
                            function ($e) use ($ks) {
                                return array_filter($e, function ($k) use ($ks) {
                                    return in_array($k, $ks);
                                }, ARRAY_FILTER_USE_KEY);
                            },
                            $data
                        ));
                    } else {
                        if (array_key_exists($p, $data)) {
                            // this is a selector of the type /a/b
                            $data = &$data[$p];
                        } else {
                            return response404NotFound(
                                sprintf('Path %s not found in given record', $arguments['seek'])
                            );
                        }
                    }
                }
            }
            // success
            return response200OK(['value' => $data]);
            break;
        //
        case 'set':
            // get arguments
            $database_name = $arguments['database'];
            $key = $arguments['key'];
            $value = $arguments['value'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            // store data
            $res = Data::set($database_name, $key, $value);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        case 'delete':
            // get arguments
            $database_name = $arguments['database'];
            $key = $arguments['key'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            // remove entry
            $res = Data::del($database_name, $key);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        case 'chown':
            // get arguments
            $database_name = $arguments['database'];
            $owner = $arguments['owner'];
            // make sure the user has access to the DB
            $res = Data::canAccess($database_name);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // only administrators can change ownership
            if (Core::getUserRole() != 'administrator') {
                return response401UnauthorizedMsg('Only administrators can change ownership of databases.');
            }
            // ---
            // store data
            $res = Data::set_ownership($database_name, $owner);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        case 'chmod':
            // get arguments
            $database_name = $arguments['database'];
            $access = $arguments['access'];
            // make sure the user has access to the DB
            $enforce_ownership_check = boolval($access == 'private');
            $res = Data::canAccess($database_name, $enforce_ownership_check);
            if (!$res['success']) {
                return response401UnauthorizedMsg($res['data']);
            }
            // ---
            // store data
            $res = ['success' => false, 'data' => 'Unknown error'];
            switch ($access) {
                case 'public':
                    $res = Data::set_public_access($database_name);
                    break;
                //
                case 'private':
                    $grant = [];
                    // parse `grant` parameter
                    if (array_key_exists('grant', $arguments)) {
                        $grant = array_map(
                            function ($user) {
                                return trim($user);
                            },
                            explode(',', $arguments['grant'])
                        );
                    }
                    $res = Data::set_private_access($database_name, $grant);
                    break;
                //
                default:
                    break;
            }
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK();
            break;
        //
        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
            break;
    }
}//execute

?>
