<?php
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\Request;

function init()
{
    global $statement1, $statement2;
    $pdo = new PDO(
        'pgsql:host=db;port=5432;dbname=rinhadb;',
        'postgre',
        'postgre',
        [
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $statement1 = $pdo->prepare("CALL inserir_transacao_2(:id, :valor, :tipo, :descricao, :saldo_atualizado, :limite_atualizado)");
    $statement2 = $pdo->prepare("
            SELECT 
                clients.limit AS limite, 
                clients.balance, 
                transactions.value, 
                transactions.type, 
                transactions.description, 
                transactions.created_at 
            FROM 
                clients 
            LEFT JOIN 
                transactions ON clients.id = transactions.client_id 
            WHERE 
                clients.id = :id 
            ORDER BY 
                transactions.created_at DESC 
            LIMIT 
            10
        ");
}

function router(Request $request)
{
    return match ($request->path()) {
        '/clientes/1/extrato' => extrato(1),
        '/clientes/2/extrato' => extrato(2),
        '/clientes/3/extrato' => extrato(3),
        '/clientes/4/extrato' => extrato(4),
        '/clientes/5/extrato' => extrato(5),
        '/clientes/1/transacoes' => transacoes($request, 1),
        '/clientes/2/transacoes' => transacoes($request, 2),
        '/clientes/3/transacoes' => transacoes($request, 3),
        '/clientes/4/transacoes' => transacoes($request, 4),
        '/clientes/5/transacoes' => transacoes($request, 5),
        // '/json' => json(),
        // '/db' => db(),
        // '/fortunes' => fortune(),
        // '/query' => query($request),
        // '/update' => updateraw($request),
        // '/info'      => info(),
        default => new Response(404, [], 'Error 404'),
    };
}

function transacoes($request, $id)
{
    // try {
        if ($id > 5) {
            return new Response(404, []);
        }

        $transactionData = ($request->post());
        if (
            !isset($transactionData['valor']) || !is_int($transactionData['valor']) ||
            !isset($transactionData['tipo']) || !in_array($transactionData['tipo'], ['c', 'd']) ||
            !isset($transactionData['descricao']) || !is_string($transactionData['descricao']) ||
            strlen($transactionData['descricao']) < 1 || strlen($transactionData['descricao']) > 10
        ) {
            return new Response(422, []);
        }

        $saldo = 0;
        $limite = 0;
        global $statement1;
        $statement1->bindParam(':id', $id, PDO::PARAM_INT);
        $statement1->bindParam(':valor', $transactionData['valor'], PDO::PARAM_INT);
        $statement1->bindParam(':tipo', $transactionData['tipo'], PDO::PARAM_STR);
        $statement1->bindParam(':descricao', $transactionData['descricao'], PDO::PARAM_STR);
        $statement1->bindParam(':saldo_atualizado', $saldo, PDO::PARAM_INT);
        $statement1->bindParam(':limite_atualizado', $limite, PDO::PARAM_INT);
        $statement1->execute();
        $response = (array) $statement1->fetchObject();
        // $saldo = 0;
        // $limite = 0;
        // $stmt = $pdo->query("CALL INSERIR_TRANSACAO_2($id, {$transactionData['valor']}, '{$transactionData['tipo']}', '{$transactionData['descricao']}', $saldo, $limite)");
        // $response = (array) $stmt->fetchObject();

        return new Response(200, [
            'Content-Type' => 'application/json',
            'Date'         => Header::$date
        ], json_encode([
            'saldo' => $response['saldo_atualizado'],
            'limite' => $response['limite_atualizado'],
        ]));
    // } catch (\Exception $e) {
    //     var_dump($e->getMessage());
    // }

    // return ngx_exit(200);
}

function extrato($id)
{
    // ngx_header_set('Content-Type', 'application/json');

    // try {
        // if (ngx_request_method() != "GET") {
        //     ngx_exit(405);
        // }

        if ($id > 5) {
            return new Response(404, []);
        }

        global $statement2;
        $statement2->execute(['id' => $id]);
        $clientWithTransactions = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $transactionsClient = array_map(function ($transaction) {
            return [
                'valor' => $transaction['value'],
                'tipo' => $transaction['type'],
                'descricao' => $transaction['description'],
                'realizada_em' => date('c', strtotime($transaction['created_at'])),
            ];
        }, $clientWithTransactions);

        $response = [
            'saldo' => [
                'total' => $clientWithTransactions[0]['balance'],
                'data_extrato' => date('c'), // Usando a data atual
                'limite' => $clientWithTransactions[0]['limite'],
            ],
            'ultimas_transacoes' => $transactionsClient,
        ];

        return new Response(200, [
            'Content-Type' => 'application/json',
            'Date'         => Header::$date
        ], json_encode($response));

        // return ngx_exit(200);

    // } catch (\Exception $e) {
    //     var_dump($e->getMessage());
    // }
}