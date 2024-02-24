#!/bin/bash
#SBATCH --job-name='FTGMM'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/113

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65da5ad07f06f
type=$(file --mime-type -b "$file0")
format=${type#*/}
echo "File is $format"
php ../../script/jobComplete.php 113