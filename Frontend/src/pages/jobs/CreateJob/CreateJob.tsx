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

import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { useMutation, useQuery } from "react-query";
import { JobType, getJobTypes } from "@/helpers/jobTypes";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { useContext, useState } from "react";
import { JobInput, JobInputParameter, createJob } from "@/helpers/jobs";
import CreationSuccess from "./CreationSuccess";
import CreationFailure from "./CreationFailure";

const CreateJob = (): JSX.Element => {
  const token = useContext(AuthContext).getToken();

  //Grabs a list of available job types
  const jobTypes = useQuery("allJobTypes", () => {
    return getJobTypes(token);
  });

  //Stores the parameters for the job, the selected job type, and name respectively.
  const [userParams, setUserParams] = useState<JobInputParameter[]>([]);
  const [selectedJobTypeID, setSelectedJobTypeID] = useState<number>();
  const [selectedJobType, setSelectedJobType] = useState<JobType>();
  const [jobName, setJobName] = useState("");
  const [uploadedFiles, setUploadedFiles] = useState();

  //Stores the state of the job request so we know what page to display
  const [requestState, setRequestState] = useState<0 | 1 | 2>(0);
  //Stores the server response string for use on the creation success screen
  const [serverResponse, setServerResponse] = useState("");
  //Deals with updating the parameters array when a new job type is selected
  const handleJobTypeChange = (_selectedJobType: number): void => {
    //Updates the selected job type
    setSelectedJobTypeID(_selectedJobType);
    //Grabs the job associated with the provided ID
    const jobDetails = jobTypes.data?.find(
      (jobType) => jobType.id === _selectedJobType
    );
    if (jobDetails) {
      setSelectedJobType(jobDetails);
      //Format them into a format that we can use
      const _params = jobDetails.parameters.map((param) => {
        //If there's an empty one for whatever reason we ignore it
        if (param.name === undefined || param.type === null) return;
        //If it's a boolean, we add the boolean identifier to the key, and set the value to the default boolean value
        if (param.type === "Boolean") {
          return {
            //This is a  bit of a hack, but its the easiest way of storing the type
            key: `¬B¬${param.name}`,
            value: Boolean(param.defaultValue ?? false),
          };
          //As above but for numbers
        } else if (param.type === "Number") {
          return {
            key: `¬N¬${param.name}`,
            value: isNaN(Number(param.defaultValue))
              ? 0
              : Number(param.defaultValue),
          };
          //As above but for strings
        } else {
          return {
            key: `¬S¬${param.name}`,
            value: param.defaultValue ?? "",
          };
        }
      });

      //We have to cast, because we're filtering out the undefineds but TypeScript doesn't recognise it.
      setUserParams(
        _params.filter((param) => param !== undefined) as JobInputParameter[]
      );
    }
  };

  //Deals with the user updating a parameter value
  const handleValueChange = (
    key: string,
    value: string | number | boolean
  ): void => {
    //We map over the parameters, and if we find the one we're looking for, we update it.
    const newParams = userParams.map((param) => {
      if (param.key === key) {
        return {
          key: param.key,
          value: value,
        };
      }
      return param;
    });
    setUserParams(newParams);
  };

  const handleFileUpload = (index: number) =>{

  }

  //Sends a job creation request to the server
  const createJobRequest = useMutation(
    (params: JobInputParameter[]) => {
      //We can coerce selectedJobType here because the submit button is disabled if no job type is selected
      const toCreate: JobInput = {
        jobID: selectedJobTypeID!,
        jobName: jobName,
        parameters: params,
      };
      return createJob(toCreate, token);
    },
    {
      onSuccess: (data) => {
        setRequestState(1);
        setServerResponse(data.output);
      },
      onError: () => {
        setRequestState(2);
      },
    }
  );

  //Removes the type identifier from the key and sends the job creation request
  const submitJob = (): void => {
    const formattedParams = userParams.map((param) => {
      return { key: param.key.split("¬")[2], value: param.value };
    });

    createJobRequest.mutate(formattedParams);
  };

  if (requestState === 0)
    return (
      <div className="w-full">
        <Nav />
        <button onClick={() => console.log(selectedJobType?.fileUploadCount)}>
          test
        </button>
        <div className="flex flex-col w-full items-center pt-8">
          <Card className="w-full max-w-2xl">
            <CardHeader>
              <CardTitle>Create a New job</CardTitle>
            </CardHeader>
            <CardContent>
              <form className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="job-type">Job Type</Label>
                  <Select onValueChange={(e) => handleJobTypeChange(Number(e))}>
                    <SelectTrigger id="job-type">
                      <SelectValue placeholder="Select a job type" />
                    </SelectTrigger>
                    <SelectContent>
                      {jobTypes.data?.map((jobType) => {
                        return (
                          <SelectItem key={jobType.id} value={`${jobType.id}`}>
                            {jobType.name}
                          </SelectItem>
                        );
                      })}
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label htmlFor="jobName">Job Name</Label>
                  <Input
                    type="text"
                    value={jobName}
                    onChange={(e) => setJobName(e.target.value)}
                  />
                </div>
                {userParams.length !== 0 && (
                  <div className="space-y-2">
                    <Label htmlFor="parameters">Parameters</Label>
                    <div className="grid grid-cols-3">
                      {userParams.map((param) => {
                        if (param.key.startsWith("¬B¬")) {
                          return (
                            <div
                              key={param.key}
                              className="flex flex-col items-center w-full gap-2"
                            >
                              <Label htmlFor={param.key}>
                                {param.key.split("¬B¬")[1]}
                              </Label>
                              <Input
                                type="checkbox"
                                id={param.key}
                                checked={Boolean(param.value)}
                                onChange={(e) =>
                                  handleValueChange(param.key, e.target.checked)
                                }
                              />
                            </div>
                          );
                        } else if (param.key.startsWith("¬N¬")) {
                          return (
                            <div
                              key={param.key}
                              className="flex flex-col items-center w-full gap-2"
                            >
                              <Label htmlFor={param.key}>
                                {param.key.split("¬N¬")[1]}
                              </Label>
                              <Input
                                type="number"
                                id={param.key}
                                className="text-center"
                                value={
                                  isNaN(Number(param.value))
                                    ? 0
                                    : Number(param.value)
                                }
                                onChange={(e) =>
                                  handleValueChange(
                                    param.key,
                                    isNaN(Number(e.target.value))
                                      ? 0
                                      : Number(e.target.value)
                                  )
                                }
                              />
                            </div>
                          );
                        } else {
                          return (
                            <div
                              key={param.key}
                              className="flex flex-col items-center w-full gap-2"
                            >
                              <Label htmlFor={param.key}>
                                {param.key.split("¬N¬")[1]}
                              </Label>
                              <Input
                                type="text"
                                id={param.key}
                                value={param.value as string}
                                onChange={(e) =>
                                  handleValueChange(param.key, e.target.value)
                                }
                              />
                            </div>
                          );
                        }
                      })}
                    </div>
                  </div>
                )}

                {selectedJobType?.fileUploadCount !== 0 && selectedJobType?.fileUploadCount !== undefined && (
                  <div className="p-2">
                    <Label htmlFor="parameters">File Upload</Label>
                    {Array.from({
                      length: selectedJobType?.fileUploadCount ?? 0,
                    }).map((_, index) => (
                      <div
                        className="flex flex-col items-center w-full gap-2"
                        key={index}
                      >
                        <Label htmlFor={`fUpload${index}`}>
                          Upload File {index}
                        </Label>
                        <Input type="file" onChange={(e) => e.target.value}/>
                      </div>
                    ))}
                  </div>
                )}
              </form>
            </CardContent>

            <CardFooter>
              <Button
                disabled={selectedJobTypeID === undefined}
                onClick={() => submitJob()}
              >
                Create job
              </Button>
            </CardFooter>
          </Card>
        </div>
      </div>
    );

  if (requestState === 1)
    return <CreationSuccess serverResponse={serverResponse} />;

  return <CreationFailure />;
};

export default CreateJob;
