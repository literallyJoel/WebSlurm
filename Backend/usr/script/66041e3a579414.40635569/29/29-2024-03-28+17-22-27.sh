#!/bin/bash
#SBATCH --job-name='AMT13'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/66041e3a579414.40635569/29//slurmout
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a74702397-1-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile0="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a74702397-extracted/file${SLURM_ARRAY_TASK_ID}"

diff $arrayfile0 $arrayfile1
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/29/markcomplete > /dev/null 2>&1