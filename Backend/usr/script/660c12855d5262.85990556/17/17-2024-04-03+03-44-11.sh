#!/bin/bash
#SBATCH --job-name='WC1'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/17//slurmout

file0=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/17/660cc2583e52b
wc -c $file0
curl -X POST http://localhost:8080/api/jobs/17/markcomplete > /dev/null 2>&1