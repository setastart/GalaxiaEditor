<?php
/* Copyright 2017-2021 Ino Detelić & Zaloa G. Ramos

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

namespace Galaxia;


use JetBrains\PhpStorm\ArrayShape;

class Editor {

    public bool   $debug   = false;
    public string $version = '';

    public string $dir       = '';
    public string $dirLayout = '';
    public string $dirLogic  = '';
    public string $dirView   = '';

    public string $logic     = '';
    public string $view      = '';
    public string $layout    = 'layout-default';
    public string $homeSlug  = 'page';
    public string $imageSlug = 'image';

    public array $locales = [
        'pt' => ['long' => 'pt_PT', 'full' => 'Português'],
        'en' => ['long' => 'en_US', 'full' => 'English'],
        'es' => ['long' => 'es_ES', 'full' => 'Castellano'],
    ];

    public string $translateEndpoint = '';

    public function __construct(string $dir) {
        $this->dir       = rtrim($dir, '/') . '/';
        $this->dirLayout = $this->dir . 'src/GalaxiaEditor/layout/';
        $this->dirLogic  = $this->dir . 'src/GalaxiaEditor/template/';
        $this->dirView   = $this->dir . 'src/GalaxiaEditor/template/';
    }


    static function pageUsers(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultUsers.php';
    }

    static function linkUser(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultUser.php';
    }

    static function pagePasswords(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultPasswords.php';
    }

    static function linkPassword(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultPassword.php';
    }

    static function pageHistory(): array {
        return require dirname(__DIR__, 2) . '/GalaxiaEditor/config/default/gcDefaultHistory.php';
    }

    static function pageDev(): array {
        return [
            'gcPerms'      => 'dev',
            'gcPageType'   => 'gcpLinkToItem',
            'gcMenuTitle'  => 'Dev',
            'gcMenuShow'   => ['gcPerms' => ['dev']],
            'geLinkToItem' => ['dev'],
        ];
    }

    static function pageStatsGoaccess(string $logFile, string $dir): array {
        return [
            'gcPageType'    => 'gcpGoaccessStats',
            'gcMenuTitle'   => 'Basic Stats',
            'gcMenuShow'    => true,
            'gcGoaccessLog' => $logFile,
            'gcGoaccessDir' => $dir,
        ];
    }

    static function pageChat(string $name): array {
        return [
            'gcPageType'  => 'gcpChat',
            'gcMenuTitle' => $name,
            'gcMenuShow'  => true,
        ];
    }


    static function linkToItem(string $title, string $root, string $id): array {
        return [
            'gcPageType'   => 'gcpLinkToItem',
            'gcMenuTitle'  => $title,
            'gcMenuShow'   => true,
            'geLinkToItem' => [$root, $id],
        ];
    }


    static function separator() {
        return ['gcPageType' => 'gcpSeparator'];
    }




    const shapeSectionListItem = [
        'gcPerms'       => 'array',
        'gcPageType'    => 'string',
        'gcMenuTitle'   => 'string',
        'gcTitleSingle' => 'string',
        'gcTitlePlural' => 'string',
        'gcMenuShow'    => 'array|bool',
        'gcColNames'    => 'array',
        'gcList'        => 'array',
        'gcItem'        => 'array',
    ];

    #[ArrayShape(Editor::shapeSectionListItem)]
    static function sectionListItem(
        ?array     $perms = null,
        array|bool $showOnMenu = true,
        string     $titleMenu = '',
        string     $titleSingle = '',
        string     $titlePlural = '',
        array      $columnNames = [],
        array      $list = [],
        array      $item = [],
    ): array {
        return [
            'gcPerms'       => $perms,
            'gcPageType'    => 'gcpListItem',
            'gcMenuTitle'   => $titleMenu,
            'gcTitleSingle' => $titleSingle,
            'gcTitlePlural' => $titlePlural,
            'gcMenuShow'    => $showOnMenu,
            'gcColNames'    => $columnNames,
            'gcList'        => $list,
            'gcItem'        => $item,
        ];
    }



    const shapeList = [
        'gcSelect'        => 'array',
        'gcSelectLJoin'   => 'array',
        'gcSelectOrderBy' => 'array',
        'gcLinks'         => 'array',
        'gcColumns'       => 'array',
        'gcFilterInts'    => 'array',
        'gcFilterTexts'   => 'array',
    ];

    #[ArrayShape(Editor::shapeList)]
    static function list(
        array $select,
        array $join,
        array $order,
        array $links,
        array $columns,
        array $filterInt,
        array $filterText,
        array $perms = null,
    ): array {
        return [
            'gcPerms'         => $perms,
            'gcSelect'        => $select,
            'gcSelectLJoin'   => $join,
            'gcSelectOrderBy' => $order,
            'gcLinks'         => $links,
            'gcColumns'       => $columns,
            'gcFilterInts'    => $filterInt,
            'gcFilterTexts'   => $filterText,
        ];
    }




    static function columnThumb(
        string $table,
        array  $cols = ['imgSlug'],
    ): array {
        return [
            'label'        => '',
            'cssClass'     => 'flexT',
            'gcColContent' => [['dbTab' => $table, 'dbCols' => $cols, 'colType' => 'thumb']],
        ];
    }

    static function columnSingle(
        string $label = 'Title',
        string $class = 'flex3',
        string $table = '',
        array  $cols = [],
        string $type = 'text',
    ): array {
        return [
            'label'        => $label,
            'cssClass'     => $class,
            'gcColContent' => [['dbTab' => $table, 'dbCols' => $cols, 'colType' => $type]],
        ];
    }

    static function columnTitle(
        string $table,
        string $col,
        string $class = 'flex1',
    ): array {
        return [
            'label'        => 'Title',
            'cssClass'     => $class,
            'gcColContent' => [['dbTab' => $table, 'dbCols' => [$col], 'colType' => 'text']],
        ];
    }

    static function columnSlug(
        string $table,
        string $col,
        string $class = 'flex2',
    ): array {
        return [
            'label'        => 'Slug',
            'cssClass'     => $class,
            'gcColContent' => [['dbTab' => $table, 'dbCols' => [$col], 'colType' => 'slug']],
        ];
    }

    static function columnDate(
        string $label,
        string $table,
        string $col,
        string $class = 'flexD',
    ): array {
        return [
            'label'        => $label,
            'cssClass'     => $class,
            'gcColContent' => [['dbTab' => $table, 'dbCols' => [$col], 'colType' => 'date']],
        ];
    }

    static function columnTag(
        string $label,
        string $table,
        string $col,
        array  $perms = null,
    ): array {
        return [
            'gcPerms'      => $perms,
            'label'        => $label,
            'cssClass'     => 'flexT tags',
            'gcColContent' => [['dbTab' => $table, 'dbCols' => [$col], 'colType' => 'tag']],
        ];
    }

    static function columnTimestamp(
        string $label,
        string $table,
        string $col = 'timestampModified',
    ): array {
        return [
            'label'        => $label,
            'cssClass'     => 'flexD',
            'gcColContent' => [['dbTab' => $table, 'dbCols' => [$col], 'colType' => 'timestamp']],
        ];
    }

    static function columnPosition(
        string $table,
        string $col = 'position',
    ): array {
        return [
            'label'        => 'Pos',
            'cssClass'     => 'flexP',
            'gcColContent' => [['dbTab' => $table, 'dbCols' => [$col], 'colType' => 'pos']],
        ];
    }




    const shapeItem = [
        'gcTable'         => 'string',
        'gcColKey'        => 'string',
        'gcVisit'         => 'bool|int|array',
        'gcUpdateOnlyOwn' => 'bool',
        'gcRedirect'      => 'bool',
        'gcInsert'        => 'array',
        'gcSelect'        => 'array',
        'gcSelectLJoin'   => 'array',
        'gcSelectExtra'   => 'array',
        'gcUpdate'        => 'array',
        'gcDelete'        => 'array',
        'gcInputs'        => 'array',
        'gcInputsWhere'   => 'array',
        'gcModules'       => 'array',
        'gcInfo'          => 'array',
    ];

    #[ArrayShape(Editor::shapeItem)]
    static function item(
        string         $table,
        string         $titleCol,
        bool|int|array $visit = false,
        bool           $onlyUpdateOwn = false,
        bool           $redirect = false,
        array          $insert = [],
        array          $select = [],
        array          $join = [],
        array          $extra = [],
        array          $update = [],
        array          $delete = [],
        array          $inputs = [],
        array          $inputsWhere = [],
        array          $modules = [],
        array          $info = [],
    ): array {
        return [
            'gcTable'         => $table,
            'gcColKey'        => $titleCol,
            'gcVisit'         => $visit,
            'gcUpdateOnlyOwn' => $onlyUpdateOwn,
            'gcRedirect'      => $redirect,
            'gcInsert'        => $insert,
            'gcSelect'        => $select,
            'gcSelectLJoin'   => $join,
            'gcSelectExtra'   => $extra,
            'gcUpdate'        => $update,
            'gcDelete'        => $delete,
            'gcInputs'        => $inputs,
            'gcInputsWhere'   => $inputsWhere,
            'gcModules'       => $modules,
            'gcInfo'          => $info,
        ];
    }



    const shapeSectionListImage = [
        'gcPerms'       => 'array',
        'gcPageType'    => 'string',
        'gcMenuTitle'   => 'string',
        'gcTitleSingle' => 'string',
        'gcTitlePlural' => 'string',
        'gcMenuShow'    => 'bool',
        'gcImageTypes'  => 'array',
        'gcImagesInUse' => 'array',
        'gcImageList'   => 'array',
        'gcImage'       => 'array',
    ];

    #[ArrayShape(Editor::shapeSectionListImage)]
    static function sectionListImage(
        ?array     $perms = null,
        array|bool $showOnMenu = true,
        string     $titleMenu = '',
        string     $titleSingle = '',
        string     $titlePlural = '',
        array      $types = [],
        array      $inUse = [],
        array      $list = [],
        array      $image = [],
    ): array {
        return [
            'gcPerms'       => $perms,
            'gcPageType'    => 'gcpImages',
            'gcMenuTitle'   => $titleMenu,
            'gcTitleSingle' => $titleSingle,
            'gcTitlePlural' => $titlePlural,
            'gcMenuShow'    => $showOnMenu,
            'gcImageTypes'  => $types,
            'gcImagesInUse' => $inUse,
            'gcImageList'   => $list,
            'gcImage'       => $image,
        ];
    }




    const shapeField = [
        'gcTable'               => 'string',
        'gcModuleType'          => 'string',
        'gcModuleTitle'         => 'string',
        'gcModuleShowUnused'    => 'bool',
        'gcModuleDeleteIfEmpty' => 'array',
        'gcModuleMultiple'      => 'array',
        'gcSelect'              => 'array',
        'gcSelectLJoin'         => 'array',
        'gcSelectOrderBy'       => 'array',
        'gcSelectExtra'         => 'array',
        'gcUpdate'              => 'array',
        'gcFieldOrder'          => 'array',
        'gcInputs'              => 'array',
        'gcInputsWhereCol'      => 'array',
        'gcInputsWhereParent'   => 'array',
    ];

    #[ArrayShape(Editor::shapeField)]
    static function field(
        string $table = '',
        string $title = '',
        bool   $showUnused = false,
        array  $deleteIfEmpty = [],
        array  $multiple = [],
        array  $select = [],
        array  $join = [],
        array  $order = [],
        array  $extra = [],
        array  $update = [],
        array  $fieldOrder = [],
        array  $inputs = [],
        array  $inputsWhereCol = [],
        array  $inputsWhereParent = [],
    ): array {
        return [
            'gcTable'               => $table,
            'gcModuleType'          => 'fields',
            'gcModuleTitle'         => $title,
            'gcModuleShowUnused'    => $showUnused,
            'gcModuleDeleteIfEmpty' => $deleteIfEmpty,
            'gcModuleMultiple'      => $multiple,
            'gcSelect'              => $select,
            'gcSelectLJoin'         => $join,
            'gcSelectOrderBy'       => $order,
            'gcSelectExtra'         => $extra,
            'gcUpdate'              => $update,
            'gcFieldOrder'          => $fieldOrder,
            'gcInputs'              => $inputs,
            'gcInputsWhereCol'      => $inputsWhereCol,
            'gcInputsWhereParent'   => $inputsWhereParent,
        ];
    }




    const shapeFieldMulti = [
        'reorder' => 'bool',
        'unique'  => 'array',
        'label'   => 'string',
        'gallery' => 'bool',
    ];

    #[ArrayShape(Editor::shapeFieldMulti)]
    static function fieldMulti(
        bool    $reorder = false,
        array   $unique = [],
        ?string $label = null,
        bool    $gallery = false,
    ): array {
        return [
            'reorder' => $reorder,
            'unique'  => $unique,
            'label'   => $label,
            'gallery' => $gallery,
        ];
    }



    static function fieldConnect(
        string $souT,
        string $conT,
        string $tarT,
        array  $extra,
        string $tarLabel,
        bool   $multi = true,
    ): array {
        $souId    = $souT . 'Id';
        $conId    = $conT . 'Id';
        $conField = $conT . 'Field';
        $tarId    = $tarT . 'Id';

        return [
            'gcTable'               => $conT,
            'gcModuleType'          => 'fields',
            'gcModuleTitle'         => '',
            'gcModuleShowUnused'    => ['gcPerms' => ['dev']],
            'gcModuleDeleteIfEmpty' => [$tarId],
            'gcModuleMultiple'      => $multi ? [$conT => ['reorder' => true, 'unique' => [$tarId], 'label' => $tarLabel]] : [],

            'gcSelect'        => [$conT => [$conId, $souId, $conField, 'position', $tarId]],
            'gcSelectLJoin'   => [],
            'gcSelectOrderBy' => [$conT => [$tarId => 'ASC']],
            'gcSelectExtra'   => [$tarT => [$tarId, ...$extra]],
            'gcUpdate'        => [$conT => [$tarId, 'position']],

            'gcInputs' => [
                $tarId => [
                    'type'           => 'select',
                    'geExtraOptions' => [$tarT => [$tarId => [...$extra]]],
                    'nullable'       => true,
                ],
            ],

            'gcInputsWhereCol' => [
                $conT => [$tarId => ['type' => 'select']],
            ],

            'gcInputsWhereParent' => [],
        ];
    }


}
