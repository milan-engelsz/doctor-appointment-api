<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class InvalidAppointmentTransition extends ConflictHttpException {}
