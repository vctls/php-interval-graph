<div class='intvg'>
    <?php foreach ($vs as $k => $v) : ?>
        <?php if ($v[2] === $v[3]): // Isolated date.?>
            <div class='bar bar-date bar<?= $k; ?>' style='left:<?= $v[0] ?>%;' data-title='<?= $v[4] ?>'></div>
        <?php else: ?>
            <div class='bar bar-intv bar<?= $k; ?>'
                 style='left:<?= $v[0] ?>%;right:<?= 100 - $v[1] ?>%;background-color:<?= $v[6] ?>'
                 data-title="<?= $v[4] . ' ➔ ' . $v[5] . (isset($v[7]) ? ' : ' . $v[7] : '') ?>"
            >
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <div class="bar" style="pointer-events: none; width: 100%; height:50%; position: absolute; top: 50%;
    background: repeating-linear-gradient(
    to right,
    transparent ,
    transparent <?= 5 * $s ?>%,
    #000000 <?= 5 * $s ?>%,
    #000000 <?= 5 * $s + 0.2 ?>%
  );"></div>
</div>