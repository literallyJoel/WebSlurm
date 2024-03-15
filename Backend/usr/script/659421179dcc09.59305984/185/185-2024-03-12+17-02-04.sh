#!/bin/bash
#SBATCH --job-name='OT'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/185//slurmout

out0=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/185/out0
out1=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/185/out1
echo '1701' > $out0;
echo '120324' > $out1;
php /root/coding-projects/WebSlurm/Backend/routes/../script/jobComplete.php 185