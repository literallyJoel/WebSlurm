#!/bin/bash
#SBATCH --job-name='WCArray'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/18//slurmout-%a

wc -c $arrayfile
curl -X POST http://localhost:8080/api/jobs/18/markcomplete > /dev/null 2>&1