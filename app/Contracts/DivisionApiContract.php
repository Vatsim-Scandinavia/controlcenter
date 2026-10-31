<?php

namespace App\Contracts;

use App\Contracts\DivisionApi\EndorsementApi;
use App\Contracts\DivisionApi\ExamApi;
use App\Contracts\DivisionApi\RosterApi;
use App\Contracts\DivisionApi\TrainingApi;
use App\Facades\DivisionApi;

/**
 * The full division API surface, composed from focused contracts: roster,
 * endorsements, exams and training (mentor/examiner) operations.
 *
 * This composite is kept as the single binding key and facade accessor so the
 * existing call sites and the {@see DivisionApi} facade continue to
 * resolve one object implementing every operation. New drivers (core or
 * module-provided) implement this composite; consumers may type-hint a narrower
 * sub-contract where they only need part of the surface.
 */
interface DivisionApiContract extends EndorsementApi, ExamApi, RosterApi, TrainingApi {}
