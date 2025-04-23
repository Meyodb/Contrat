<?php

namespace App\Http\Controllers;

use App\Models\Avenant;
use App\Models\Contract;
use App\Models\Employee;
use Illuminate\Http\Request;

class AvantController extends Controller
{
    public function generatePdf(Avenant $avenant)
    {
        $contract = $avenant->contract;
        $employee = $contract->employee;
        $monthlyHours = $contract->monthly_hours;

        $data = [
            'avenant_number' => $avenant->number,
            'contract_date' => $contract->signing_date,
            'effective_date' => $avenant->effective_date,
            'signing_date' => $avenant->signing_date,
            'employee_name' => $employee->name,
            'employee_gender' => $employee->gender,
            'new_hours' => $avenant->new_hours,
            'new_salary' => $avenant->new_salary,
            'monthly_hours' => $monthlyHours,
            'motif' => $avenant->motif,
        ];

        // ... existing code ...
    }

    public function preview(Avenant $avenant)
    {
        $contract = $avenant->contract;
        $employee = $contract->employee;
        $monthlyHours = $contract->monthly_hours;

        $data = [
            'avenant_number' => $avenant->number,
            'contract_date' => $contract->signing_date,
            'effective_date' => $avenant->effective_date,
            'signing_date' => $avenant->signing_date,
            'employee_name' => $employee->name,
            'employee_gender' => $employee->gender,
            'new_hours' => $avenant->new_hours,
            'new_salary' => $avenant->new_salary,
            'monthly_hours' => $monthlyHours,
            'motif' => $avenant->motif,
        ];

        // ... existing code ...
    }
} 