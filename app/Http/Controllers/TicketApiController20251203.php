<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TicketApiController extends Controller
{
    public function index()
    {
        $tickets = DB::select("
        SELECT 
            t.uuid,
            t.name AS ticket_name,
            COALESCE(ts_history.name, ts_default.name) AS ticket_status,
            COALESCE(th.created_at, t.created_at) AS status_created_at,
            t.description,
            t.start_date,
            t.due_date,

            -- ticket assigned user
            tu.user_id,
            u.name AS user_name,

            -- comment data
            tc.id AS comment_id,
            tc.comment,
            tc.user_id AS comment_user_id,
            uc.name AS comment_user_name,
            tc.created_at AS comment_created_at

        FROM tickets t

        LEFT JOIN ticket_users tu 
            ON t.id = tu.ticket_id
        
        LEFT JOIN users u
            ON u.id = tu.user_id

        LEFT JOIN ticket_histories th 
            ON th.ticket_id = t.id
            AND th.created_at = (
                SELECT MAX(th2.created_at)
                FROM ticket_histories th2
                WHERE th2.ticket_id = t.id
            )

        LEFT JOIN ticket_statuses ts_history 
            ON ts_history.id = th.ticket_status_id

        LEFT JOIN ticket_statuses ts_default 
            ON ts_default.id = t.ticket_status_id

        LEFT JOIN ticket_comments tc 
            ON tc.ticket_id = t.id

        LEFT JOIN users uc
            ON uc.id = tc.user_id

        ORDER BY COALESCE(th.created_at, t.created_at) DESC, tc.created_at DESC
    ");

        return response()->json($tickets);
    }
}
