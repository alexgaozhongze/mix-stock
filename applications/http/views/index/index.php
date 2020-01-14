<?php
$this->title = 'go beyond';
?>

<script type="text/javascript">
  var data = <?php echo json_encode($list)?>;
  for (var i in data) {
    window.open(data[i]);
  }
</script>
