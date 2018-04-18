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
</div>