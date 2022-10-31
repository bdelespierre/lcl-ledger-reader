<?php require 'lcl.php' ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCL Ledger Reader v0.1</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js" integrity="sha256-u7e5khyithlIdTpu22PHhENmPcRdFiHRjhAuHcs05RI=" crossorigin="anonymous"></script>
    <style tyle="text/css">
        td {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="col-lg-8 mx-auto p-3 py-md-5">
        <header class="d-flex align-items-center pb-3 mb-5 border-bottom">
            <a href="/" class="d-flex align-items-center text-dark text-decoration-none">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-cash-coin me-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0z"/>
                    <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1h-.003zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195l.054.012z"/>
                    <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083c.058-.344.145-.678.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1H1z"/>
                    <path d="M9.998 5.083 10 5a2 2 0 1 0-3.132 1.65 5.982 5.982 0 0 1 3.13-1.567z"/>
                </svg>
                <span class="fs-4">LCL Ledger Reader</span>
            </a>
        </header>

        <?php if (empty($_POST['table-content'])): ?>
            <form class="form" action="index.php" method="POST">
                <div class="mb-3">
                    <label for="table-content-textarea">Copy/paste content here</label>
                    <textarea class="w-100" name="table-content" rows="15"></textarea>
                </div>
                <input class="btn btn-primary" type="submit" value="Parse">
            </form>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <td></td>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-end">Num</th>
                        <th scope="col">Type</th>
                        <th scope="col">Label</th>
                        <th scope="col" class="text-end">Credit</th>
                        <th scope="col" class="text-end">Debit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $prev = null ?>
                    <?php foreach (parse_lcl_table_content($_POST['table-content']) as $row): ?>
                        <?php $num = $row['date'] == ($prev) ? $num + 1 : 1 ?>
                        <tr>
                            <td class="text-center"><input type="checkbox"/></td>
                            <td><?php if ($row['date'] != $prev): ?><?=(new DateTime($row['date']))->format('d/m/y')?><?php endif ?></td>
                            <td class="text-end"><?=$num?></td>
                            <td><?=$row['type']?></td>
                            <td><?=$row['label']?></td>
                            <td class="text-end font-monospace"><?php if ($row['credit']): ?><?=sprintf('%.02f', $row['credit'])?> €<?php endif ?></td>
                            <td class="text-end font-monospace"><?php if ($row['debit']): ?><?=sprintf('%.02f', $row['debit'])?> €<?php endif ?></td>
                        </tr>
                        <?php $prev = $row['date'] ?>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
    <script type="text/javascript">
        $(function () {
            $('td').click(event => {
                $(':checkbox', $(event.target).parents('tr')).click();
            });

            $(':checkbox').click(event => {
                $(event.target).parents('tr').toggleClass('text-decoration-line-through text-muted');
                event.stopPropagation();
            });
        });
    </script>
</body>
</html>
