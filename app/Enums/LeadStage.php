<?php

namespace App\Enums;

enum LeadStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Proposal = 'proposal';
    case ClosedWon = 'closed_won';
    case ClosedLost = 'closed_lost';
}
