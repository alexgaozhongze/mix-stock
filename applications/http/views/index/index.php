<?php
$this->title = 'go beyond';
?>

<table>
    <tr>
        <td style="padding:8px 18px;">code</td>
        <td style="padding:8px 18px;">avg_cjs</td>
        <td style="padding:8px 18px;">up</td>
        <td style="padding:8px 18px;">cjs</td>
        <td style="padding:8px 18px;">pre</td>
    </tr>
    <?php foreach($list as $value): ?>
    <tr>
        <td style="padding:8px 18px;"><?= $value['code'] ?></td>
        <td style="padding:8px 18px;"><?= $value['avg_cjs'] ?></td>
        <td style="padding:8px 18px;"><?= $value['up'] ?></td>
        <td style="padding:8px 18px;"><?= $value['cjs'] ?></td>
        <td style="padding:8px 18px;"><?= $value['pre'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
