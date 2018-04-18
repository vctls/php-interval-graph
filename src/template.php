<div class='intvg'>
    <?php foreach ($vs as $k => $v) : ?>
        <?php if (count($v) === 2 ): // Isolated date.?>
            <div class='bar bar-date bar<?= $k; ?>' style='left:<?= $v[0] ?>%;' data-title='<?= $v[1] ?>'></div>
        <?php else: ?>
            <div class='bar bar-intv bar<?= $k; ?>'
                 style='left:<?= $v[0] ?>%;right:<?= $v[1] ?>%;background-color:<?= $v[2] ?>'
                 data-title="<?= $v[3] . ' ➔ ' . $v[4] . (isset($v[5]) ? ' : ' . $v[5] : '') ?>">
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