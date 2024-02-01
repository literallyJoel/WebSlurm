#!/bin/bash
#SBATCH --job-name='x'
#SBATCH --output=out-'x'
#SBATCH --time=20:00
#SBATCH --ntasks=1
#SBATCH --mem-per-cpu=100
echo "Script started at $(date)"
sleep '120'
echo "Script ended at $(date)"
mv out-'x' ~/out-'x'