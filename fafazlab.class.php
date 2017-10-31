<?php

/**
*    @class  fafazlab
*    @author fafaz
*    @email: fafazlab@gmail.com,
*    @homepage: https://fafazlab.com
*    @brief  fafazlab 모듈의 high class
*
**/

class fafazlab extends ModuleObject
{

    private $devMode = true;

    // 스키마 목록
    private $schemas = array(
        // array('table' => 'fafazlab', 'columns' => array('primary_id', 'module_srl', 'member_srl', 'total_score')),
    );

    // 트리거 목록
    private $triggers = array(
        // array('document.insertDocument', 'fafazlab', 'controller', 'triggerAfterInsertDocument', 'after'),
		// array('document.deleteDocument', 'fafazlab', 'controller', 'triggerAfterDeleteDocument', 'after'),
        array('display', 'fafazlab', 'controller', 'triggerBeforeDisplay', 'before'),
    );

    // 모듈 최초 설치 시
    public function moduleInstall()
    {
        return new Object();
    }

    // 업데이트 확인
    public function checkUpdate()
    {
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');
        // 트리거 등록 여부 확인
        foreach ($this->triggers as $trigger) {
            if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) {
                return true;
            }
        }

        if ($this->devMode) {
            $oDB = &DB::getInstance();
            // DB 컬럼 존재 여부 확인
            foreach ($this->schemas as $item) {
                if ($oDB->isTableExists($item[table])) {
                    foreach ($item[columns] as $column) {
                        if (!$oDB->isColumnExists($item[table], $column)) {
                            // debugPrint($item[table].'의 '.$column.' 컬럼이 존재하지 않습니다.');
                            return true;
                        }
                    }
                }
            }
        }
    }

    // 업데이트
    public function moduleUpdate()
    {
        $oModuleModel = &getModel('module');
        $oModuleController = &getController('module');

        // 등록 안된 트리거 있으면 다시 INSERT
        foreach ($this->triggers as $trigger) {
            if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) {
                $oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
            }
        }

        // ONLY DEV MODE
        if ($this->devMode) {
            $oDB = &DB::getInstance();
            foreach ($this->schemas as $item) {
                if ($oDB->isTableExists($item[table])) {
                    foreach ($item[columns] as $column) {
                        if (!$oDB->isColumnExists($item[table], $column)) {
                            $oDB->dropTable($item[table]);
                            $file_path = $this->module_path.'schemas/'.$item[table].'.xml';
                            if (file_exists($file_path)) {
                                $oDB->createTableByXmlFile($file_path);
                                return new Object(0, '기존 테이블을 삭제 후 재등록 하였습니다. \n문제가 반복된다면 테이블 구조를 다시 확인해보세요');
                            } else {
                                return new Object(-1, $file_path.' 파일이 필요합니다.');
                            }
                        }
                    }
                }
            }
        }
    }

    public function moduleUninstall()
    {
        $oModuleModel = getModel('module');
        $oModuleController = getController('module');
        foreach ($this->triggers as $trigger) {
            if ($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) {
                $oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
            }
        }
        return new Object();
    }

    public function recompileCache()
    {
    }
}
