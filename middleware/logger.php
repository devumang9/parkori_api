<?php
	function logActivity($db, $data = []) {
		try {
			$stmt = $db->prepare("INSERT INTO system_log (LogEvent, `Table`, OldData, NewData, Emp_ID, Cust_ID, LogAction, IPAddress, UserAgent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

			$stmt->execute([
				$data['event']   			?? 'CUSTOM',
				$data['table']   			?? '',
				isset($data['old']) 		? json_encode($data['old'], JSON_UNESCAPED_UNICODE) : null,
				isset($data['new']) 		? json_encode($data['new'], JSON_UNESCAPED_UNICODE) : null,
				$data['emp_id']  			?? null,
				$data['cust_id'] 			?? null,
				$data['action']  			?? null,
				$_SERVER['REMOTE_ADDR']     ?? null,
				$_SERVER['HTTP_USER_AGENT'] ?? null
			]);

		} catch (\PDOException $e) {

			// ✅ Log DB-specific error
			error_log("LOG DB ERROR: " . $e->getMessage());

		} catch (\Exception $e) {

			// ✅ Log any other error
			error_log("LOG GENERAL ERROR: " . $e->getMessage());

		}
	}

	function getRow($db, $table, $idField, $id) {
		$stmt = $db->prepare("SELECT * FROM {$table} WHERE {$idField} = ?");
		$stmt->execute([$id]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
?>