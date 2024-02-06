#!/bin/bash
#SBATCH --job-name='ConTest2'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/2024-02-01_18-18-56
#SBATCH --time=20:00
#SBATCH --ntasks=1
#SBATCH --mem-per-cpu=100
echo "Script started at $(date)"
sleep '120000'
echo "Script ended at $(date)"



php ../../script/jobComplete.php 52
rm -- /root/coding-projects/WebSlurm/Backend/routes/../usr/script/659421179dcc09.59305984-5-2024-02-01_18-18-56.sh