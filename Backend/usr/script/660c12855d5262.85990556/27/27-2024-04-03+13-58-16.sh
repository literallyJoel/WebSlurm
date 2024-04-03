#!/bin/bash
#SBATCH --job-name='HMMMM'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/27//slurmout-%a
#SBATCH --array=0-11


arrayfile="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/27/660d524879a85-extracted/file${SLURM_ARRAY_TASK_ID}"

wc -c $arrayfile
curl -X POST http://localhost:8080/api/jobs/27/markcomplete > /dev/null 2>&1