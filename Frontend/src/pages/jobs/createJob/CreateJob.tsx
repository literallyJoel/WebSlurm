import { Label } from "@/components/shadui/ui/label";
import {
  SelectValue,
  SelectTrigger,
  SelectItem,
  SelectContent,
  Select,
} from "@/components/shadui/ui/select";
import { Input } from "@/components/shadui/ui/input";
import { Button } from "@/components/shadui/ui/button";
import Nav from "@/components/Nav";

import "@uppy/core/dist/style.css";
import "@uppy/dashboard/dist/style.css";
import "@uppy/drag-drop/dist/style.css";
import "@uppy/file-input/dist/style.css";
import "@uppy/progress-bar/dist/style.css";

import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { useMutation, useQuery } from "react-query";
import { JobType, getJobTypes } from "@/helpers/jobTypes";
import { useAuthContext } from "@/providers/AuthProvider";
import React, { useEffect, useState } from "react";
import { CreateJobRequest, JobParameter, createJob } from "@/helpers/jobs";
import CreationSuccess from "./CreationSuccess";
import CreationFailure from "./CreationFailure";
import { Textarea } from "@/components/shadui/ui/textarea";
import Uppy from "@uppy/core";
import { Dashboard } from "@uppy/react";
import { generateFileId } from "@/helpers/files";
import { getUserOrganisations } from "@/helpers/organisations";
import Noty from "noty";
import { Combobox } from "@/components/shadui/ui/combobox";
interface props {
  uppy: Uppy<Record<string, unknown>, Record<string, unknown>>;
  setFileId: React.Dispatch<React.SetStateAction<string | undefined>>;
  fileId: string | undefined;
  isUploadComplete: boolean;
  resetUppy: () => void;
  setAllowedTypes: React.Dispatch<React.SetStateAction<string[] | undefined>>;
  allowedTypes: string[] | undefined;
  setArrayJobCount: React.Dispatch<React.SetStateAction<number>>;
}

const CreateJob = ({
  uppy,
  setFileId,
  fileId,
  isUploadComplete,
  resetUppy,
  setAllowedTypes,
  allowedTypes,
  setArrayJobCount,
}: props): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();

  //Grabs a list of available job types
  const jobTypes = useQuery("allJobTypes", () => {
    return getJobTypes(token);
  });
  const user = useAuthContext().getUser();
  //Stores the parameters for the job, the selected job type, and name respectively.
  const [userParams, setUserParams] = useState<JobParameter[]>([]);
  const [selectedJobTypeID, setSelectedJobTypeID] = useState<number>();
  const [selectedJobType, setSelectedJobType] = useState<JobType>();
  const [jobName, setJobName] = useState("");
  //Stores the state of the job request so we know what page to display
  const [requestState, setRequestState] = useState<0 | 1 | 2>(0);
  //Stores the server response string for use on the creation success screen
  const [serverResponse, setServerResponse] = useState("");
  const [formattedOrganisations, setFormattedOrganisations] = useState<
    { label: string; value: string }[]
  >([]);
  const [selectedOrganisation, setSelectedOrganisation] = useState("");

  useEffect(() => {
    if (jobName === "" && selectedJobType) {
      setJobName(
        `${selectedJobType.jobTypeName}-${
          user.name
        }-${new Date().toLocaleDateString("en-gb")}`
      );
    }
  }, [selectedJobType]);
  useEffect(() => {
    async function updateUppyInfo() {
      if (selectedJobType && selectedJobType.arrayJobCount !== 0) {
        setArrayJobCount(selectedJobType.arrayJobCount);
      } else {
        setArrayJobCount(1);
      }

      if (
        selectedJobType &&
        selectedJobType.hasFileUpload &&
        (selectedJobType.script.includes("$file1=") ||
          selectedJobType.script.includes("$arrayfile"))
      ) {
        await getFID();
        setAllowedTypes([".zip"]);
      } else if (selectedJobType && selectedJobType.hasFileUpload) {
        await getFID();
        setAllowedTypes(undefined);
      }
    }

    updateUppyInfo();
  }, [selectedJobType]);

  useEffect(() => {
    resetUppy();
  }, [allowedTypes, selectedJobType]);

  const getFID = async () => {
    if (fileId === undefined) {
      setFileId((await generateFileId(token)).fileId);
    }
  };

  //Deals with updating the parameters array when a new job type is selected
  const handleJobTypeChange = (_selectedJobType: number): void => {
    //Updates the selected job type
    setSelectedJobTypeID(_selectedJobType);

    //Grabs the job associated with the provided ID
    const jobDetails = jobTypes.data?.find(
      (jobType) => Number(jobType.jobTypeId) === _selectedJobType
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
        _params.filter((param) => param !== undefined) as JobParameter[]
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

  //Sends a job creation request to the server
  const createJobRequest = useMutation(
    (params: JobParameter[]) => {
      //We can coerce selectedJobType here because the submit button is disabled if no job type is selected
      const toCreate: CreateJobRequest = {
        jobTypeId: selectedJobTypeID!,
        jobName: jobName,
        fileId: fileId,
        parameters: params,
        organisationId:
          selectedOrganisation === "" ? undefined : selectedOrganisation,
      };
      return createJob(toCreate, token);
    },
    {
      onSuccess: (data) => {
        setRequestState(1);
        setFileId(undefined);
        setServerResponse(data.output);
        resetUppy();
      },
      onError: () => {
        setRequestState(2);
      },
    }
  );

  useQuery(
    "getOrganisations",
    () => {
      return getUserOrganisations(token);
    },
    {
      onSuccess: (data) => {
        const formattedData = data.map((org) => ({
          label: org.organisationName,
          value: org.organisationId,
        }));
        setFormattedOrganisations(formattedData);
      },
      onError: () => {
        new Noty({
          text: "An error occured fetching your organisations. Please try again later.",
          type: "error",
          timeout: 5000,
        }).show();
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
        <div className="flex flex-col w-full items-center pt-8">
          <Card className="w-full max-w-2xl">
            <CardHeader>
              <CardTitle>Create a New job</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="job-type">Job Type</Label>
                  <Select onValueChange={(e) => handleJobTypeChange(Number(e))}>
                    <SelectTrigger id="job-type">
                      <SelectValue placeholder="Select a job type" />
                    </SelectTrigger>
                    <SelectContent>
                      {jobTypes.data?.map((jobType) => {
                        return (
                          <SelectItem
                            key={jobType.jobTypeId}
                            value={`${jobType.jobTypeId}`}
                          >
                            {jobType.jobTypeName}
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
                {selectedJobType?.jobTypeDescription && (
                  <div>
                    <Label>Description</Label>
                    <Textarea
                      disabled
                      value={selectedJobType.jobTypeDescription}
                      rows={
                        selectedJobType.jobTypeDescription.split("\n").length
                      }
                    />
                  </div>
                )}
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
                                {param.key.split("¬S¬")[1]}
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

                {selectedJobType?.hasFileUpload &&
                  selectedJobType?.hasFileUpload !== undefined &&
                  (selectedJobType?.hasFileUpload as unknown as string) !==
                    "0" && (
                    <div className="p-2">
                      <Label
                        className="flex flex-col pb-2"
                        htmlFor="parameters"
                      >
                        File Upload
                      </Label>

                      <Label className="text-sm">
                        If you are uploading multiple files, please upload a ZIP
                        file containing the files, with the names file0, file1,
                        etc.
                      </Label>

                      <Dashboard
                        id="dashboard"
                        uppy={uppy}
                        plugins={["Webcam"]}
                        doneButtonHandler={undefined}
                      />
                    </div>
                  )}
                <div className="w-full">
                  <Label>Share with an organisation?</Label>
                  <Combobox
                    value={selectedOrganisation}
                    setValue={setSelectedOrganisation}
                    items={formattedOrganisations}
                    itemTypeName="Organisation"
                    className="w-full"
                  />
                </div>
              </div>
            </CardContent>

            <CardFooter>
              <Button
                disabled={
                  selectedJobType === undefined ||
                  ((selectedJobType.hasFileUpload as unknown as string) !==
                    "0" &&
                    !isUploadComplete)
                }
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
