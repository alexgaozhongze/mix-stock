<?php
$this->title = 'GoBeyond';
?>

<table>
    <tr>
        <td style="padding:3px 8px;">code</td>
        <td style="padding:3px 8px;">price</td>
        <td style="padding:3px 8px;">up</td>
        <td style="padding:3px 8px;">nprice</td>
        <td style="padding:3px 8px;">nup</td>
        <td style="padding:3px 8px;">num</td>
        <td style="padding:3px 8px;">time</td>
    </tr>
    <?php foreach($list as $value): ?>
    <tr>
        <td style="padding:3px 8px;"><?= $value['code'] ?></td>
        <td style="padding:3px 8px;"><?= $value['price'] ?></td>
        <td style="padding:3px 8px;"><?= $value['up'] ?></td>
        <td style="padding:3px 8px;"><?= $value['nprice'] ?></td>
        <td style="padding:3px 8px;"><?= $value['nup'] ?></td>
        <td style="padding:3px 8px;"><?= $value['num'] ?></td>
        <td style="padding:3px 8px;"><?= $value['time'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
