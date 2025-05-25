<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use App\Infrastructure\Persistence\PdoExpenseRepository;
use App\Infrastructure\Persistence\PdoUserRepository;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
        private readonly PdoUserRepository $userPdo,
        private readonly PdoExpenseRepository $expensePdo,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the expenses page

        // Hints:
        // - use the session to get the current user ID
        // - use the request query parameters to determine the page number and page size
        // - use the expense service to fetch expenses for the current user

        // parse request parameters
        $userId = $_SESSION['user_id']; // TODO: obtain logged-in user ID from session
        $user = $this->userPdo->find($userId);
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);
        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('m'));

        $expenses = $this->expenseService->list($user, $year, $month, $page, $pageSize);
        $allYears = $this->expenseService->listYears($user);

        $totalExpensesCount = $this->expenseService->countExpenses($user, $year, $month);
        $totalPages = ceil($totalExpensesCount / self::PAGE_SIZE);
        
        $queryParams = http_build_query([ 'year' => $request->getQueryParams()['year'], 'month' => $request->getQueryParams()['month'] ]);
        $curentUrl = "/expenses?$queryParams";

        return $this->render($response, 'expenses/index.twig', [
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
            'year' => strval($year),
            'month' => strval($month),
            'allYears' => $allYears,
            'totalPages' => $totalPages,
            'currentUrl' => $curentUrl,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        // TODO: implement this action method to display the create expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        $categories = json_decode($_ENV['EXPENSE_CATEGORIES'], true);

        return $this->render($response, 'expenses/create.twig', ['categories' => $categories]);
    }

    public function store(Request $request, Response $response): Response
    {
        // TODO: implement this action method to create a new expense

        // Hints:
        // - use the session to get the current user ID
        // - use the expense service to create and persist the expense entity
        // - rerender the "expenses.create" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $errors = [];
        $userId = $_SESSION['user_id'];
        $user = $this->userPdo->find($userId);
        $categories = json_decode($_ENV['EXPENSE_CATEGORIES'], true);

        if (!$user) {
            $errors['user'] = 'User not found!';
        }

        $data = $request->getParsedBody();
        $amount = floatval($data['amount'] ?? 0);
        $inputDate = new DateTimeImmutable($data['date']);
        $today = new DateTimeImmutable();

        if ($inputDate > $today) {
            $errors['date'] = 'The date can not be in future!';
        }

        if ($data['category'] === '') {
            $errors['category'] = 'Please select a category';
        }

        if ($amount <= 0) {
            $errors['amount'] = 'Please enter a valid amount value';
        }

        if ($data['description'] === '') {
            $errors['description'] = 'Please enter a description';
        }

        if (!empty($errors)) {
            return $this->render($response,'expenses/create.twig', ['errors' => $errors, 'categories' => $categories]);
        }

        $this->expenseService->create($user, $amount, $data['description'], $inputDate, $data['category']);

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to display the edit expense page

        // Hints:
        // - obtain the list of available categories from configuration and pass to the view
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not

        $errors = [];

        $userId = $_SESSION['user_id'];
        $expenseId = (int) $routeParams['id'];

        $categories = json_decode($_ENV['EXPENSE_CATEGORIES'], true);

        $expense = $this->expensePdo->find($expenseId);
        $date = $expense->date->format('Y-m-d');
        
        if($expense->userId !== $userId) {
            $response = $response->withStatus(403);
            $errors['user'] = 'Not alowed to edit this record!';
        }

        return $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => $categories, 'date' => $date, 'errors' => $errors]);
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to update an existing expense

        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        $errors = [];
        $userId = $_SESSION['user_id'];
        $expenseId = (int) $routeParams['id'];
        $categories = json_decode($_ENV['EXPENSE_CATEGORIES'], true);

        $expense = $this->expensePdo->find($expenseId);
        $date = $expense->date->format('Y-m-d');

        if($expense->userId !== $userId) {
            $response = $response->withStatus(403);
            $errors['user'] = 'Not alowed to edit this record!';

            $this->render($response, 'expenses/edit.twig', ['expense' => $expense, 'categories' => $categories, 'date' => $date, 'errors' => $errors]);
        }

        $body = $request->getParsedBody();
        $inputDate = new DateTimeImmutable($body['date']);

        $this->expenseService->update($expense, (float) $body['amount'], $body['description'], $inputDate, $body['category'] );

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        // TODO: implement this action method to delete an existing expense

        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - call the repository method to delete the expense
        // - redirect to the "expenses.index" page

        $errors = [];
        $userId = $_SESSION['user_id'];
        $expenseId = (int) $routeParams['id'];
        $expense = $this->expensePdo->find($expenseId);

        if($expense->userId !== $userId) {
            $response = $response->withStatus(403);
            $errors['user'] = 'Not alowed to edit this record!';
        }

        $this->expensePdo->delete($expenseId);

        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }
}
