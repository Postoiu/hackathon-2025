<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function list(User $user, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        // TODO: implement this and call from controller to obtain paginated list of expenses
        $offset = ($pageNumber -1) * $pageSize;
        $yearString = strval($year);
        $monthString = $month < 10 ? '0' . strval($month) : strval($month);

        return $this->expenses->findBy(['user_id' => $user->id, 'year' => $yearString, 'month' => $monthString], $offset, $pageSize);
    }

    public function listYears(User $user): array {
        return $this->expenses->listExpenditureYears($user);
    }

    public function countExpenses(User $user, int $year, int $month): int {
        $monthString = $month < 10 ? '0' . strval($month) : strval($month);
        return $this->expenses->countBy(['user_id' => $user->id, 'year' => strval($year), 'month'=> $monthString]);
    }

    public function create(
        User $user,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to create a new expense entity, perform validation, and persist
        $amounCents = $amount * 100;
        $categoryUpper = ucfirst($category);
        // TODO: here is a code sample to start with
        $expense = new Expense(null, $user->id, $date, $categoryUpper, (int)$amounCents, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
    }

    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
