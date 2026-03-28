<?php

class ObjectTreeExporter extends IPSModule
{
    private static $DEFAULT_FILENAME = 'objecttree.json';

    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('ExportFilename', self::$DEFAULT_FILENAME);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->SetStatus(102);
    }

    public function ExportObjectTree()
    {
        $filename = trim($this->ReadPropertyString('ExportFilename'));
        if (empty($filename)) {
            $filename = self::$DEFAULT_FILENAME;
        }

        // Nur Dateiname erlaubt, kein Pfad-Traversal
        $filename = basename($filename);
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.json';
        }

        $tree = $this->BuildTree(0);
        $json = json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo $json;
        return true;
    }

    private function BuildTree($parentID)
    {
        $children = IPS_GetChildrenIDs($parentID);
        $nodes = [];

        foreach ($children as $id) {
            $obj = IPS_GetObject($id);
            $node = [
                'id'       => $id,
                'name'     => $obj['ObjectName'],
                'type'     => $obj['ObjectType'],
                'typeText' => $this->GetObjectTypeText($obj['ObjectType']),
                'ident'    => $obj['ObjectIdent'],
                'position' => $obj['ObjectPosition'],
                'info'     => $obj['ObjectInfo'],
                'hidden'   => $obj['ObjectIsHidden'],
                'readonly' => $obj['ObjectIsReadOnly'],
                'icon'     => $obj['ObjectIcon'],
            ];

            switch ((int)$obj['ObjectType']) {
                case 0: // Category
                    break;

                case 1: // Instance
                    $inst = IPS_GetInstance($id);
                    $node['moduleID']   = $inst['ModuleInfo']['ModuleID'];
                    $node['moduleName'] = $inst['ModuleInfo']['ModuleName'];
                    $node['status']     = $inst['InstanceStatus'];
                    break;

                case 2: // Variable
                    $var = IPS_GetVariable($id);
                    $node['varType']     = $var['VariableType'];
                    $node['varTypeText'] = $this->GetVarTypeText($var['VariableType']);
                    $node['value']       = GetValue($id);
                    $node['profile']     = $var['VariableCustomProfile'] !== ''
                                            ? $var['VariableCustomProfile']
                                            : $var['VariableProfile'];
                    break;

                case 3: // Script
                    $script = IPS_GetScript($id);
                    $node['scriptType'] = $script['ScriptType'];
                    break;

                case 4: // Event
                    break;

                case 5: // Media
                    break;

                case 6: // Link
                    $link = IPS_GetLink($id);
                    $node['targetID'] = $link['TargetID'];
                    break;
            }

            $childNodes = $this->BuildTree($id);
            if (!empty($childNodes)) {
                $node['children'] = $childNodes;
            }

            $nodes[] = $node;
        }

        return $nodes;
    }

    private function GetObjectTypeText($type)
    {
        $map = [
            0 => 'Category',
            1 => 'Instance',
            2 => 'Variable',
            3 => 'Script',
            4 => 'Event',
            5 => 'Media',
            6 => 'Link',
        ];
        return isset($map[$type]) ? $map[$type] : 'Unknown';
    }

    private function GetVarTypeText($type)
    {
        $map = [
            0 => 'Boolean',
            1 => 'Integer',
            2 => 'Float',
            3 => 'String',
        ];
        return isset($map[$type]) ? $map[$type] : 'Unknown';
    }
}
