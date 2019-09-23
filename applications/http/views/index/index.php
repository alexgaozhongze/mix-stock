<?php
$this->title = 'go beyond';
?>
<head>
<style type='text/css'>
body {
  text-align: center;
}

td {
  margin: 100px;
  overflow: auto;
}

</style>
</head>

<table width='88%' style="margin-left:10%;font-size:2.8em;">
    <tr>
        <td>code</td>
        <td>avg_cjs</td>
        <td>up</td>
        <td>cjs</td>
        <td>pre</td>
    </tr>
    <?php foreach($list as $value): ?>
    <tr>
        <td><?= $value['code'] ?></td>
        <td><?= $value['avg_cjs'] ?></td>
        <td><?= $value['up'] ?></td>
        <td><?= $value['cjs'] ?></td>
        <td><?= $value['pre'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
