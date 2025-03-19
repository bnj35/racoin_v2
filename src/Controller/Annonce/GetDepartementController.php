<?php

namespace Controller\Annonce;

use model\Departement;

class GetDepartementController
{

    protected $departments = array();

    public function getAllDepartments()
    {
        return Departement::orderBy('nom_departement')->get()->toArray();
    }
}
