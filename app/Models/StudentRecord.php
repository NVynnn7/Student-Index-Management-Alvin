<?php

namespace App\Models;

class StudentRecord extends Person
{
    private string $studentId;
    private string $email;
    private float $gpa;

    public function __construct(string $studentId, string $name, string $email, float $gpa)
    {
        parent::__construct($name);
        $this->studentId = $studentId;
        $this->email = $email;
        $this->gpa = $gpa;
    }

    public function getStudentId(): string
    {
        return $this->studentId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getGpa(): float
    {
        return $this->gpa;
    }

    public function getRole(): string
    {
        return 'Student';
    }

    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'name' => $this->getName(),
            'email' => $this->email,
            'gpa' => $this->gpa,
        ];
    }
}
