<?php

class Person
{
	public $person_id;
	public $user_id;
	public $name;
	public $description;

	function __construct($person_id)
	{
		$sql = "SELECT * FROM people WHERE id='$person_id'";
		$person_rslt = db_exec($sql) or errDie("Unable to retrieve person.");
		$person_data = pg_fetch_array($person_rslt);

		$this->person_id = $person_data["id"];
		$this->user_id = $person_data["user_id"];
		$this->name = $person_data["name"];
		$this->description = $person_data["description"];
	}
}