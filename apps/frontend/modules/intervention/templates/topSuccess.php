<h1>Top des interventions</h1>
<?$cpt = 0 ; foreach ($top as $t) : $cpt++;?>
<p><? echo $cpt; ?> - <? echo link_to($t['nom'], '@parlementaire?slug='.$t['slug']); ?> (<? echo $t['nb']; ?> intervention(s))</p>
<?endforeach; ?>