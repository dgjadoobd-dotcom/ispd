<?php

/**
 * TaskService — Business logic for the Task Management module.
 *
 * Covers Requirements 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8:
 *   - Task CRUD with status, priority, due date
 *   - Task status transition validation
 *   - Task assignment to employees
 *   - Overdue task detection
 *   - Bulk task assignment
 *   - Completion rate reports
 *
 * @see database/migrations/2024_01_03_011_task_module.sql
 */
class TaskService extends BaseService
{
    private const STATUSES = ['pending', 'in_progress', 'completed', 'cancelled'];
    private const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function getTasks(array $filters = []): array
    {
        $page  = max(1, (int)($filters['page']  ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $params      = [];
        $conditions  = [];

        if (!empty($filters['status'])) {
            $conditions[] = "t.status = ?";
            $params[]    = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $conditions[] = "t.priority = ?";
            $params[]    = $filters['priority'];
        }
        if (!empty($filters['assigned_to'])) {
            $conditions[] = "t.assigned_to = ?";
            $params[]    = (int)$filters['assigned_to'];
        }
        if (!empty($filters['branch_id'])) {
            $conditions[] = "t.branch_id = ?";
            $params[]    = (int)$filters['branch_id'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(t.title LIKE ? OR t.description LIKE ?)";
            $like        = '%' . $filters['search'] . '%';
            $params[]    = $like;
            $params[]    = $like;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        try {
            $countRow = $this->db->fetchOne("SELECT COUNT(*) AS total FROM tasks t{$where}", $params);
            $total   = (int)($countRow['total'] ?? 0);

            $sql = "SELECT t.*,
                          u1.full_name AS created_by_name,
                          u2.full_name AS assigned_to_name
                   FROM tasks t
                   LEFT JOIN users u1 ON u1.id = t.created_by
                   LEFT JOIN users u2 ON u2.id = t.assigned_to
                   {$where}
                   ORDER BY
                       CASE t.status
                           WHEN 'in_progress' THEN 1
                           WHEN 'pending'   THEN 2
                           WHEN 'completed' THEN 3
                           WHEN 'cancelled' THEN 4
                           ELSE 5
                       END,
                       CASE t.priority
                           WHEN 'urgent' THEN 1
                           WHEN 'high'   THEN 2
                           WHEN 'medium' THEN 3
                           WHEN 'low'    THEN 4
                           ELSE 5
                       END,
                       t.due_date ASC,
                       t.created_at DESC
                   LIMIT {$limit} OFFSET {$offset}";

            $data        = $this->db->fetchAll($sql, $params);
            $totalPages = $total > 0 ? (int)ceil($total / $limit) : 1;

            return [
                'data'       => $data,
                'total'      => $total,
                'page'       => $page,
                'perPage'    => $limit,
                'totalPages' => $totalPages,
                'hasNext'   => $page < $totalPages,
                'hasPrev'   => $page > 1,
            ];
        } catch (\Throwable $e) {
            $this->logError('getTasks failed', $e, $filters);
            return ['data' => [], 'total' => 0, 'page' => $page, 'perPage' => $limit, 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        }
    }

    public function getTask(int $id): ?array
    {
        try {
            $task = $this->db->fetchOne(
                "SELECT t.*,
                        u1.full_name AS created_by_name,
                        u2.full_name AS assigned_to_name,
                        b.name AS branch_name
                 FROM tasks t
                 LEFT JOIN users u1 ON u1.id = t.created_by
                 LEFT JOIN users u2 ON u2.id = t.assigned_to
                 LEFT JOIN branches b ON b.id = t.branch_id
                 WHERE t.id = ? LIMIT 1",
                [$id]
            );

            if (!$task) {
                return null;
            }

            $task['history'] = $this->db->fetchAll(
                "SELECT th.*, u.full_name AS user_name
                 FROM task_history th
                 LEFT JOIN users u ON u.id = th.user_id
                 WHERE th.task_id = ?
                 ORDER BY th.created_at ASC",
                [$id]
            );

            $task['assignments'] = $this->db->fetchAll(
                "SELECT ta.*,
                        u1.full_name AS assigned_to_name,
                        u2.full_name AS assigned_by_name
                 FROM task_assignments ta
                 LEFT JOIN users u1 ON u1.id = ta.assigned_to
                 LEFT JOIN users u2 ON u2.id = ta.assigned_by
                 WHERE ta.task_id = ?
                 ORDER BY ta.assigned_at DESC",
                [$id]
            );

            return $task;
        } catch (\Throwable $e) {
            $this->logError('getTask failed', $e, ['id' => $id]);
            return null;
        }
    }

    public function createTask(array $data): int
    {
        $dueDate = !empty($data['due_date']) ? $data['due_date'] : null;

        $taskId = $this->create('tasks', [
            'title'       => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'status'      => 'pending',
            'priority'   => in_array($data['priority'] ?? 'medium', self::PRIORITIES) ? $data['priority'] : 'medium',
            'due_date'   => $dueDate,
            'created_by'  => (int)($data['created_by'] ?? 0) ?: null,
            'assigned_to' => (int)($data['assigned_to'] ?? 0) ?: null,
            'branch_id'   => (int)($data['branch_id'] ?? 0) ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->logHistory($taskId, 'created', null, 'pending', $data['created_by'] ?? null);

        return $taskId;
    }

    public function updateTask(int $id, array $data): void
    {
        $allowed = ['title', 'description', 'priority', 'status', 'due_date', 'assigned_to', 'branch_id'];

        $update = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        $update['updated_at'] = date('Y-m-d H:i:s');

        if (!empty($update)) {
            $oldTask = $this->findById('tasks', $id);
            $this->update('tasks', $id, $update);

            foreach ($update as $field => $newValue) {
                if (isset($oldTask[$field])) {
                    $this->logHistory($id, 'updated', $oldTask[$field], $newValue, $_SESSION['user_id'] ?? null);
                }
            }
        }
    }

    public function updateStatus(int $id, string $newStatus): void
    {
        if (!in_array($newStatus, self::STATUSES)) {
            throw new \RuntimeException("Invalid status: {$newStatus}");
        }

        $task = $this->findById('tasks', $id);
        if (!$task) {
            throw new \RuntimeException("Task #{$id} not found.");
        }

        $oldStatus = $task['status'];

        if ($oldStatus === $newStatus) {
            return;
        }

        $update = [
            'status'     => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($newStatus === 'completed') {
            $update['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->update('tasks', $id, $update);
        $this->logHistory($id, 'status_changed', $oldStatus, $newStatus, $_SESSION['user_id'] ?? null);
    }

    public function assignTask(int $taskId, int $employeeId, string $notes = ''): void
    {
        $task = $this->findById('tasks', $taskId);
        if (!$task) {
            throw new \RuntimeException("Task #{$taskId} not found.");
        }

        $employee = $this->db->fetchOne(
            "SELECT id, full_name FROM users WHERE id = ? AND is_active = 1 LIMIT 1",
            [$employeeId]
        );
        if (!$employee) {
            throw new \RuntimeException("Employee #{$employeeId} not found or inactive.");
        }

        $assignedBy = $_SESSION['user_id'] ?? null;

        $this->db->insert('task_assignments', [
            'task_id'     => $taskId,
            'assigned_to'  => $employeeId,
            'assigned_by' => $assignedBy,
            'assigned_at' => date('Y-m-d H:i:s'),
            'notes'      => $notes,
        ]);

        $this->update('tasks', $taskId, [
            'assigned_to' => $employeeId,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $this->logHistory($taskId, 'assigned', null, (string)$employeeId, $assignedBy);
    }

    public function bulkAssign(array $taskIds, int $employeeId): int
    {
        $employee = $this->db->fetchOne(
            "SELECT id, full_name FROM users WHERE id = ? AND is_active = 1 LIMIT 1",
            [$employeeId]
        );
        if (!$employee) {
            throw new \RuntimeException("Employee #{$employeeId} not found or inactive.");
        }

        $assignedBy = $_SESSION['user_id'] ?? null;
        $count = 0;

        foreach ($taskIds as $taskId) {
            $taskId = (int)$taskId;
            if ($taskId <= 0) {
                continue;
            }

            $this->db->insert('task_assignments', [
                'task_id'     => $taskId,
                'assigned_to' => $employeeId,
                'assigned_by' => $assignedBy,
                'assigned_at' => date('Y-m-d H:i:s'),
                'notes'      => 'Bulk assignment',
            ]);

            $this->update('tasks', $taskId, [
                'assigned_to' => $employeeId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        return $count;
    }

    public function deleteTask(int $id): void
    {
        $task = $this->findById('tasks', $id);
        if (!$task) {
            throw new \RuntimeException("Task #{$id} not found.");
        }

        $this->delete('tasks', $id);
    }

    public function getTasksByDate(string $date, ?int $branchId = null): array
    {
        $params = [];
        $where  = "WHERE DATE(t.due_date) = ?";
        $params[] = $date;

        if ($branchId !== null) {
            $where  .= " AND t.branch_id = ?";
            $params[] = $branchId;
        }

        try {
            return $this->db->fetchAll(
                "SELECT t.*, u.full_name AS assigned_to_name
                 FROM tasks t
                 LEFT JOIN users u ON u.id = t.assigned_to
                 {$where}
                 ORDER BY t.priority ASC, t.created_at DESC",
                $params
            );
        } catch (\Throwable $e) {
            $this->logError('getTasksByDate failed', $e);
            return [];
        }
    }

    public function getAllEmployees(?int $branchId = null): array
    {
        $params = [];
        $sql    = "SELECT id, full_name, phone, role_id, branch_id
                   FROM users
                   WHERE is_active = 1";

        if ($branchId !== null) {
            $sql    .= " AND branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY full_name ASC";

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            $this->logError('getAllEmployees failed', $e);
            return [];
        }
    }

    public function getOverdueTasks(?int $branchId = null): array
    {
        $params = [];
        $where  = "WHERE t.status NOT IN ('completed', 'cancelled')
                    AND t.due_date < datetime('now')";

        if ($branchId !== null) {
            $where  .= " AND t.branch_id = ?";
            $params[] = $branchId;
        }

        try {
            return $this->db->fetchAll(
                "SELECT t.*, u.full_name AS assigned_to_name
                 FROM tasks t
                 LEFT JOIN users u ON u.id = t.assigned_to
                 {$where}
                 ORDER BY t.due_date ASC",
                $params
            );
        } catch (\Throwable $e) {
            $this->logError('getOverdueTasks failed', $e);
            return [];
        }
    }

    public function getCompletionReport(?int $branchId = null): array
    {
        $params = $branchId !== null ? [$branchId] : [];
        $filter = $branchId !== null ? ' AND t.branch_id = ?' : '';

        try {
            $summary = $this->db->fetchOne(
                "SELECT
                     COUNT(*) AS total,
                     SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) AS pending,
                     SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
                     SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                     SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                     SUM(CASE WHEN t.status NOT IN ('completed', 'cancelled')
                                  AND t.due_date < datetime('now') THEN 1 ELSE 0 END) AS overdue
                  FROM tasks t
                  WHERE 1=1{$filter}",
                $params
            ) ?? [];

            $total      = (int)($summary['total'] ?? 0);
            $completed = (int)($summary['completed'] ?? 0);
            $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;

            $byEmployee = $this->db->fetchAll(
                "SELECT
                     u.full_name AS employee_name,
                     COUNT(t.id) AS total_tasks,
                     SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed
                  FROM tasks t
                  LEFT JOIN users u ON u.id = t.assigned_to
                  WHERE t.assigned_to IS NOT NULL{$filter}
                  GROUP BY t.assigned_to, u.full_name
                  ORDER BY total_tasks DESC",
                $params
            );

            foreach ($byEmployee as &$row) {
                $row['completion_rate'] = $row['total_tasks'] > 0
                    ? round(($row['completed'] / $row['total_tasks']) * 100, 1)
                    : 0.0;
            }

            return [
                'summary'         => $summary,
                'completion_rate'  => $completionRate,
                'by_employee'    => $byEmployee,
            ];
        } catch (\Throwable $e) {
            $this->logError('getCompletionReport failed', $e);
            return ['summary' => [], 'completion_rate' => 0.0, 'by_employee' => []];
        }
    }

    private function logHistory(int $taskId, string $action, ?string $oldValue, ?string $newValue, ?int $userId): void
    {
        try {
            $this->db->insert('task_history', [
                'task_id'   => $taskId,
                'user_id'  => $userId,
                'action'   => $action,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logError('logHistory failed', $e);
        }
    }
}