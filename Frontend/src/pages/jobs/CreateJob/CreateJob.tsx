import { Label } from "@/shadui/ui/label";
import {
  SelectValue,
  SelectTrigger,
  SelectItem,
  SelectContent,
  Select,
} from "@/shadui/ui/select";
import { Input } from "@/shadui/ui/input";
import { Button } from "@/shadui/ui/button";
import Nav from "@/components/Nav";
import { useMutation, useQuery } from "react-query";
import { useContext, useEffect, useState } from "react";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { JobType, getJobTypes } from "@/pages/admin/settings/JobTypes/jobTypes";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { Job, JobParameter, createJob } from "../jobs";

const CreateJob = (): JSX.Element => {
  const [selectedJobTypeID, setSelectedJobTypeID] = useState("");
  const [selectedJobType, setSelectedJobType] = useState<JobType>();
  const [parameters, setParameters] = useState<JobParameter[]>([]);
  const token = useContext(AuthContext).getToken();
  const allJobTypes = useQuery("getAlljobs", () => {
    return getJobTypes(token);
  });

  const _createJob = useMutation("createJob", (job: Job) => {
    return createJob(job, token);
  });

  useEffect(() => {
    if (allJobTypes.data) {
      const updatedParameters = allJobTypes.data.flatMap(
        (jobType) =>
          jobType.parameters?.map((parameter) => ({
            key: parameter.name,
            value:
              parameter.type === "Boolean"
                ? Boolean(parameter.defaultValue ?? false)
                : parameter.type === "Number"
                ? Number.parseFloat((parameter.defaultValue as string) ?? "0")
                : (parameter.defaultValue as string) ?? "",
          })) ?? []
      );

      setParameters((prev) => [...prev, ...updatedParameters]);
    }
  }, [allJobTypes.data]);

  const getStringParamValue = (key: string): string => {
    const _param = parameters.find((param) => param.key === key);
    if (_param && typeof _param.value === "string") {
      return _param.value;
    }

    return "";
  };

  const getBooleanParamValue = (key: string): boolean => {
    const _param = parameters.find((param) => param.key === key);
    if (_param && typeof _param.value === "boolean") {
      return _param.value;
    }

    return false;
  };

  const getNumberParamValue = (key: string): number => {
    const _param = parameters.find((param) => param.key === key);
    if (_param && typeof _param.value === "number") {
      return _param.value;
    }

    return 0;
  };

  const setParam = (key: string, value: string | number | boolean): void => {
    const _parameters = parameters;
    const index = _parameters.findIndex((param) => param.key === key);
    if (index === -1) {
      _parameters.push({ key: key, value: value });
    } else {
      _parameters[index] = { key: key, value: value };
    }
    setParameters(_parameters);
  };

  return (
    <div className="w-full">
      <Nav />
      <div className="flex flex-col w-full items-center pt-8">
        <Card className="w-full max-w-2xl">
          <CardHeader>
            <CardTitle>Create a New job</CardTitle>
          </CardHeader>
          <CardContent>
            <form className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="job-type">Job Type</Label>
                <Select
                  value={selectedJobTypeID}
                  onValueChange={(value) => {
                    setSelectedJobTypeID(value);
                    setSelectedJobType(
                      allJobTypes.data?.find(
                        (jobType) => `${jobType.id}` === value
                      )
                    );
                  }}
                >
                  <SelectTrigger id="job-type">
                    <SelectValue placeholder="Select a job type" />
                  </SelectTrigger>
                  <SelectContent>
                    {allJobTypes.data?.map((jobType) => {
                      return (
                        <SelectItem value={`${jobType.id}`}>
                          {jobType.name}
                        </SelectItem>
                      );
                    })}
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="parameters">Parameters</Label>
                <div className="grid grid-cols-3">
                  {selectedJobType ? (
                    selectedJobType.parameters.map((parameter) => {
                      return (
                        <div>
                          <Label htmlFor={parameter.name}>
                            {parameter.name}
                          </Label>
                          {parameter.type === "String" ? (
                            <Input
                              type="text"
                              value={getStringParamValue(parameter.name)}
                              onChange={(e) =>
                                setParam(parameter.name, e.target.value)
                              }
                            />
                          ) : parameter.type === "Boolean" ? (
                            <Input
                              type="checkbox"
                              checked={getBooleanParamValue(parameter.name)}
                              onChange={(e) =>
                                setParam(parameter.name, e.target.checked)
                              }
                            />
                          ) : (
                            <Input
                              type="number"
                              value={getNumberParamValue(parameter.name)}
                              onChange={(e) =>
                                setParam(
                                  parameter.name,
                                  Number.parseFloat(e.target.value)
                                )
                              }
                            />
                          )}
                        </div>
                      );
                    })
                  ) : (
                    <></>
                  )}
                </div>
              </div>
            </form>
          </CardContent>

          <CardFooter>
            <Button
              onClick={() =>
                _createJob.mutate({
                  jobID: selectedJobTypeID,
                  parameters: parameters,
                })
              }
              className="w-full"
              type="submit"
            >
              Create job
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};

export default CreateJob;
