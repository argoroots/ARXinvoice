<?php

    // *** CONFIGURATION START ***

    define('DIR_INVOICES', 'invoices/');     //Invoices folder
    define('EDIT_KEY',     'abc123');        //Secret string what allow edit invoices
    define('TAX',          0.0);             //Tax rate
    define('COMPANY',      '<b>Company LLC</b><br />Street, City<br /><b>IBAN:</b');             //Company info

    // *** CONFIGURATION END ***


    $url = 'http'. (isset($_SERVER['HTTPS']) ? 's' : '') .'://'. $_SERVER['HTTP_HOST'] .'/';
    $action = explode('/', array_shift(array_keys($_GET)));
    switch($action[0]) {
        case '':
            header("HTTP/1.0 404 Not Found");
            exit();
            break;
        case EDIT_KEY:
            if($_GET['save'] == 'save_invoice') {
                $invoice['customer']['name'] = $_GET['name'];
                $invoice['customer']['address1'] = $_GET['address1'];
                $invoice['customer']['address2'] = $_GET['address2'];
                $invoice['invoice']['date'] = date('d.m.Y', strtotime($_GET['date']));
                $invoice['invoice']['number'] = $_GET['number'];
                foreach(array_keys($_GET['row_name']) as $key) {
                    if($_GET['row_name'][$key]) {
                        $sum = ((double) $_GET['row_qty'][$key] * (double) $_GET['row_price'][$key]);
                        $invoice['items'][] = array(
                            'name' => $_GET['row_name'][$key],
                            'qty' => (int) $_GET['row_qty'][$key],
                            'price' => (double) $_GET['row_price'][$key],
                            'sum' => $sum
                        );
                        $invoice['invoice']['sum'] += $sum;
                        $invoice['invoice']['tax'] += ($sum * TAX);
                    }
                }
                $invoice['invoice']['total'] = ($invoice['invoice']['sum'] + $invoice['invoice']['tax']);
                $invoice['invoice']['payment_method'] = $_GET['payment_method'];
                $id = save_invoice($invoice, $action[1]);
                header('Location: '. $url . $id);
            } else {
                $invoice = load_invoice($action[1]);
            }
            $edit = TRUE;
            break;
        default:
            $invoice = load_invoice($action[0]);
            break;
    }

    function load_invoice($id) {
        if(file_exists(DIR_INVOICES .$id)) {
            $invoice = json_decode(file_get_contents(DIR_INVOICES .$id), TRUE);
        } else {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
        if(!is_array($invoice)) {
            $invoice['customer']['name'] = '';
            $invoice['customer']['address1'] = '';
            $invoice['customer']['address2'] = '';
            $invoice['invoice']['date'] = date('d.m.Y');
            $invoice['invoice']['number'] = '';
            $invoice['invoice']['sum'] = 0;
            $invoice['invoice']['tax'] = 0;
            $invoice['invoice']['total'] = 0;
            $invoice['invoice']['payment_method'] = '';
            $invoice['items'][99999999] = array(
                        'name' => '',
                        'qty' => 1,
                        'price' => '',
                        'sum' => ''
                    );
        }
        if(!is_array($invoice['items'])) {
                $invoice['items'][99999999] = array(
                    'name' => '',
                    'qty' => 1,
                    'price' => '',
                    'sum' => ''
                );
        }
        return $invoice;
    }

    function save_invoice($invoice, $id) {
        $invoice['id'] = (strlen($id) == 32) ? $id : md5(time()*rand());
        if(file_exists(DIR_INVOICES .$id)) {
            $invoice['file']['modified'] = time();
        } else {
            $invoice['file']['created'] = time();
        }
        file_put_contents(DIR_INVOICES .$invoice['id'], json_encode($invoice, JSON_PRETTY_PRINT));
        return $invoice['id'];
    }

    function show_value($name, $value, $edit, $number) {
        if($number == TRUE AND $edit != TRUE) $value = number_format((double) $value, 2, ',', ' ');
        if($edit == TRUE) {
            return '<input type="text" name="'. $name .'" value="' . htmlspecialchars($value) .'" />';
        } else {
            return $value;
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= $invoice['invoice']['number'] ? 'Arve '. $invoice['invoice']['number'] : 'Arve'; ?></title>
        <style type="text/css" media="screen, print">
            html, body {
                padding: 0px;
                margin: 0px;
                height: 100%;
                font-family: 'Lucida Grande', Helvetica, Arial, Verdana, sans-serif;
                font-size: 12px;
                color: #000000;
                background: #FFFFFF;
            }
            #wrap {
                width: 900px;
                margin: 0px auto 0px auto;
                padding: 40px 0px 40px 0px;
            }
            #side h1 {
                padding: 0px;
                margin: 0px;
                font-size: 40px;
                font-weight: bold;
            }
            #main {
                margin: 0px;
                padding-top: 5px;
                width: 730px;
                float: right;
            }
            #side {
                float: left;
                width: 170px;
            }
            #footer {
                position: absolute;
                bottom: 0px;
                padding-bottom: 10px;
                width: 900px;
                overflow: hidden;
            }
            #qr_code {
                margin-top: 54px;
                margin-right: -12px;
                float: right;
            }
            #items th {
                padding: 7px 10px;
                border-right: 1px solid #B8B9B9;
                background: #367DA2;
                color: #FFFFFF;
                white-space:nowrap;
            }
            #items td {
                padding: 7px 10px;
                border-bottom: 1px solid #B8B9B9;
                border-right: 1px solid #B8B9B9;
                white-space:nowrap;
            }
            #buttons {
                float: right;
            }
            #buttons a, #buttons span {
                margin-left: 10px;
                padding: 3px 15px;
                background-color: #23AA3D;
                color: #FFFFFF;
                text-align: center;
                text-decoration: none;
                font-weight: bold;
                cursor: pointer;
                -moz-border-radius: 9px;
                -webkit-border-radius: 9px;
            }
            form, fieldset {
                border: none;
                margin: 0px;
                padding: 0px;
            }
            input {
                border: none;
                margin: 1px;
                padding: 0px;
                border-bottom: 1px solid #B8B9B9;
            }
            table {
                border-spacing: 0px;
                border-collapse: separate;
            }
            td input {
                border: none;
                width: 100%;
            }
        </style>
        <style type="text/css" media="print">
            #wrap {
                padding: 200px 0px 40px 0px;
            }
            #buttons {
                display:none;
            }
            #qr_code {
                display:inline;
            }
        </style>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    </head>
    <body>
        <div id="wrap">
            <div id="side">
                <h1>
                    ARVE
                </h1>
                <div id="footer">
                    <img id="qr_code" src="http://chart.apis.google.com/chart?cht=qr&amp;chs=90x90&amp;chl=<?= $url . $invoice['id']; ?>" alt="QR" />
                    <br />
                    <?= COMPANY ?>
                </div>
            </div>
            <div id="main">
<?php if($edit == TRUE) { ?>
                <div id="buttons">
                    <span id="add_row">Lisa rida</span> <span id="save">Salvesta</span>
                </div>
                <form id="form" method="GET" action="/<?= EDIT_KEY .'/'. $invoice['id']; ?>">
                    <fieldset>
                        <input type="hidden" name="save" value="save_invoice" />
<?php } ?>
                        <div id="info">
                            <b>Maksja:</b><br />
                            <?= show_value('name', $invoice['customer']['name'], $edit, FALSE); ?><br />
                            <?= show_value('address1', $invoice['customer']['address1'], $edit, FALSE); ?><br />
                            <?= show_value('address2', $invoice['customer']['address2'], $edit, FALSE); ?><br />
                            <br />
                            <br />
                            <b>Kuupäev:</b> <?= show_value('date', $invoice['invoice']['date'], $edit, FALSE); ?><br />
                            <b>Arve number:</b> <?= show_value('number', $invoice['invoice']['number'], $edit, FALSE); ?><br />
                            <br />
                            <br />
                        </div>
                    <table id="items">
                        <tr>
                            <th style="width:500px">
                                Nimetus
                            </th>
                            <th style="width:50px">
                                Kogus
                            </th>
                            <th style="width:75px">
                                Hind
                            </th>
                            <th style="width:75px; border-right:none;">
                                Summa
                            </th>
                        </tr>
                        <tr id="empty_row" style="display:none;">
                            <td>
                                <input type="text" name="row_name[]" value="" />
                            </td>
                            <td style="text-align:center">
                                <input type="text" name="row_qty[]" value="" style="text-align:center;" />
                            </td>
                            <td style="text-align:right">
                                <input type="text" name="row_price[]" value="" style="text-align:right;" />
                            </td>
                            <td style="text-align:right; border-right:none;">
                            </td>
                        </tr>
                        <?php foreach($invoice['items'] as $item) { ?>
                        <tr class="trow">
                            <td>
                                <?= show_value('row_name[]', $item['name'], $edit, FALSE) ; ?>
                            </td>
                            <td style="text-align:center">
                                <?= show_value('row_qty[]', $item['qty'], $edit, FALSE); ?>
                            </td>
                            <td style="text-align:right">
                                <?= show_value('row_price[]', $item['price'], $edit, TRUE); ?>
                            </td>
                            <td style="text-align:right; border-right:none;">
                                <?= show_value('', $item['sum'], FALSE, TRUE); ?>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr id="sum_row1">
                            <td style="border-bottom:none; border-right:none;">
                            </td>
                            <td colspan="2" style="text-align:right;">
                                Maksumus käibemaksuta
                            </td>
                            <td style="text-align:right; border-right:none;">
                                <?= show_value('', $invoice['invoice']['sum'], FALSE, TRUE); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-bottom:none; border-right:none;">
                            </td>
                            <td colspan="2" style="text-align:right;">
                                Käibemaks <?= TAX*100 ?>%
                            </td>
                            <td style="text-align:right; border-right:none;">
                                <?= show_value('', $invoice['invoice']['tax'], FALSE, TRUE); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-bottom:none; border-right:none;">
                            </td>
                            <td colspan="2" style="text-align:right; font-weight:bold;">
                                Arve summa kokku
                            </td>
                            <td style="text-align:right; font-weight:bold; border-right:none;">
                                <?= show_value('', $invoice['invoice']['total'], FALSE, TRUE); ?>
                            </td>
                        </tr>
                    </table>
                    <br />
                    <br />
                    <br />
                    <br />
                    <?= $edit == FALSE ? $invoice['invoice']['payment_method'] : '' ?>
<?php if($edit == TRUE) { ?>
                    <select name="payment_method">
                        <?php foreach(array('Arve tasutud sularahas.', 'Arve palume tasuda 5 päeva jooksul. Viivis 0,05% päevas õigeaegselt tasumata summalt.') as $method) { ?>

                        <option value="<?= $method ?>" <?= $method == $invoice['invoice']['payment_method'] ? 'selected' : '' ?>><?= $method ?></option>
                        <?php } ?>
                    </select>
                </fieldset>
            </form>
<?php } ?>
            </div>
        </div>
        <script type="text/javascript">
            $(document).ready(function(){
                $("#add_row").click(function() {
                    $("#empty_row").clone().removeAttr('id').insertBefore("#sum_row1").show();
                    return false;
                });
                $("#save").click(function() {
                    $("#form").submit();
                    return false;
                });
            });
        </script>
    </body>
</html>
