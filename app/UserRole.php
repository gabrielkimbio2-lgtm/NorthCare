<?php

namespace App;

enum UserRole: string
{
    case Patient = 'patient';
    case Practitioner = 'practitioner';
    case Institution = 'institution';
    case Admin = 'admin';
}
