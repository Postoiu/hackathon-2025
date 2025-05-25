<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        // TODO: Implement save() method.
        $query = 'INSERT INTO expenses (id, user_id, date, category, amount_cents, description) VALUES (:id, :userId, :date, :category, :amount, :description) ON CONFLICT(id) DO UPDATE SET date=:date, category=:category, amount_cents=:amount, description=:description';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $expense->id,'userId' => $expense->userId, 'date' => $expense->date->format('Y-m-d H:i:s'), 'category' => $expense->category, 'amount' => $expense->amountCents, 'description' => $expense->description]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        // TODO: Implement findBy() method.
        $params = array_merge($criteria, ['from' => $from, 'limit' => $limit]);
        $lastKey = array_key_last($criteria);

        $query = 'SELECT * FROM expenses WHERE ';

        foreach ($criteria as $field => $_) {
            if ($field === 'year') {
                $query .= "strftime('%Y', date) = :$field";
            } else if ($field === 'month') {
                $query .= "strftime('%m', date) = :$field";
            } else {
                $query .= " $field = :$field";
            }

            if ($field !== $lastKey) {
                $query .= ' AND ';
            }
        }

        
        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :from';
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $data = $statement->fetchAll();
        $expenses = [];

        foreach ($data as $expense) {
            array_push($expenses, $this->createExpenseFromData($expense));
        }

        if (false === $data) {
            return [];
        }
        return $expenses;
    }


    public function countBy(array $criteria): int
    {
        // TODO: Implement countBy() method.
        $lastKey = array_key_last($criteria);

        $query = 'SELECT COUNT(*) AS count FROM expenses WHERE ';

        foreach ($criteria as $field => $_) {
            if ($field === 'year') {
                $query .= "strftime('%Y', date) = :$field";
            } else if ($field === 'month') {
                $query .= "strftime('%m', date) = :$field";
            } else {
                $query .= " $field = :$field";
            }

            if ($field !== $lastKey) {
                $query .= ' AND ';
            }
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($criteria);
        $data = $statement->fetchColumn();

        return $data;
    }

    public function listExpenditureYears(User $user): array
    {
        // TODO: Implement listExpenditureYears() method.
        $query = 'SELECT DISTINCT strftime(\'%Y\', date) AS year FROM expenses WHERE user_id = :userId ORDER BY year ASC';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['userId'=> $user->id]);
        $data = $statement->fetchAll();
        $normalizedData = [];

        foreach ($data as $record) {
            array_push($normalizedData, $record['year']);
        }

        return $normalizedData;
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        // TODO: Implement sumAmountsByCategory() method.
        return [];
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        // TODO: Implement averageAmountsByCategory() method.
        return [];
    }

    public function sumAmounts(array $criteria): float
    {
        // TODO: Implement sumAmounts() method.
        return 0;
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
