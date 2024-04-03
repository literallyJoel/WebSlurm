#!/bin/bash
#SBATCH --job-name='DCArr2'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/24//slurmout-%a
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/24/660d5178a4f281-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile0="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/24/660d5178a4f280-extracted/file${SLURM_ARRAY_TASK_ID}"

diff $arrayfile0 $arrayfile1
curl -X POST http://localhost:8080/api/jobs/24/markcomplete > /dev/null 2>&1