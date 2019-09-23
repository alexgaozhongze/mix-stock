<?php
$this->title = 'go beyond';
?>
<head>
<style type='text/css'>
body {
  text-align: center;
}

td {
  padding: 1em;
  overflow: auto;
}

</style>
</head>

<table style="font-size:2.5em;">
    <tr>
        <td>code</td>
        <td>up</td>
        <td>avg_cjs</td>
        <td>cjs</td>
        <td>pre</td>
    </tr>
    <?php foreach($list as $value): ?>
    <tr>
        <td><?= $value['code'] ?></td>
        <td><?= $value['up'] ?></td>
        <td><?= $value['avg_cjs'] ?></td>
        <td><?= $value['cjs'] ?></td>
        <td><?= $value['pre'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
