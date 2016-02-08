<?php

	require(ROOT_DIR.'/system/PicoDataRecord.php');

	class Module_PicoDataRecord extends BaseModule {

		public function create($fields) {
			return new PicoDataRecord($fields);
		}

	}
