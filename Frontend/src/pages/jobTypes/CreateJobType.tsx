import { useState } from "react";
import {
  type JobTypeParameter,
  extractParams,
  updateParameterList,
  type CreateJobTypeRequest,
  createJobType,
  validateParameters,
} from "@/helpers/jobTypes";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { Textarea } from "@/components/shadui/ui/textarea";
import { Label } from "@/components/shadui/ui/label";
import { Input } from "@/components/shadui/ui/input";
import { Editor } from "@monaco-editor/react";
import ParameterEntry from "@/components/jobTypes/ParameterEntry";
import { Button } from "@/components/shadui/ui/button";
import { useMutation, useQuery } from "react-query";
import { useNavigate } from "react-router-dom";
import { useAuthContext } from "@/providers/AuthProvider";
import Nav from "@/components/Nav";
import { getUserOrganisations } from "@/helpers/organisations";
import Noty from "noty";
import { Combobox } from "@/components/shadui/ui/combobox";

interface props {
  standalone?: boolean;
}
const CreateJobTypeInterface = ({ standalone }: props): JSX.Element => {
  const [jobTypeName, setJobTypeName] = useState("");
  const [isNameValid, setIsNameValid] = useState(true);
  const [jobTypeDescription, setJobTypeDescription] = useState("");
  const [isDescriptionValid, setIsDescriptionValid] = useState(true);
  const scriptStart =
    "#!/bin/bash\n#SBATCH --job-name=*{name}*\n#SBATCH --output=*{out}*";
  const [script, setScript] = useState(scriptStart);
  const [parameters, setParameters] = useState<JobTypeParameter[]>([]);
  const [hasFileUpload, setHasFileUpload] = useState(false);
  const [hasOutputFiles, setHasOutputFiles] = useState(false);
  const [outputCount, setOutputCount] = useState(0);
  const [arrayJobSupport, setArrayJobSupport] = useState(false);
  const [arrayJobCount, setArrayJobCount] = useState(0);
  const [invalidParams, setInvalidParams] = useState<number[]>([]);
  const [formattedOrganisations, setFormattedOrganisations] = useState<
    { label: string; value: string }[]
  >([]);
  const [selectedOrganisation, setSelectedOrganisation] = useState("");
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const navigate = useNavigate();
  const createJobTypeRequest = useMutation(
    "createJobType",
    (jobType: CreateJobTypeRequest) => {
      return createJobType(jobType, token);
    },
    {
      onSuccess: () => {
        new Noty({
          text: "Job Type succesfully created. You will be redirected momentaritly.",
          type: "success",
          timeout: 5000,
        }).show();
        setTimeout(() => {
          navigate("/admin/jobtypes");
        }, 5000);
      },
      onError: () => {
        new Noty({
          text: "Failed to create Job Type. Please try again later.",
          type: "error",
          timeout: 5000,
        }).show();
      },
    }
  );

  useQuery(
    "getOrganisations",
    () => {
      const moderatorOrgs = getUserOrganisations(token, undefined, 1);
      const adminOrg = getUserOrganisations(token, undefined, 2);
      return Promise.all([moderatorOrgs, adminOrg]).then((values) => {
        return values.flat();
      });
    },
    {
      enabled: !!standalone,
      onError: () => {
        new Noty({
          text: "Failed to get your organisations. Please try again later",
          type: "error",
          timeout: 5000,
        }).show();
      },
      onSuccess: (data) => {
        if (data.length === 1) {
          setSelectedOrganisation(data[0].organisationId);
        } else {
          const formattedData = data.map((org) => ({
            label: org.organisationName,
            value: org.organisationId,
          }));
          setFormattedOrganisations(formattedData);
        }
      },
    }
  );

  const createJob = (): void => {
    const _invalidParams = validateParameters(parameters);
    setInvalidParams(_invalidParams);

    setIsNameValid(jobTypeName !== "");
    setIsDescriptionValid(jobTypeDescription !== "");
    if (
      _invalidParams.length === 0 &&
      jobTypeName !== "" &&
      jobTypeDescription !== ""
    ) {
      createJobTypeRequest.mutate({
        jobTypeName: jobTypeName,
        script: script,
        parameters: parameters,
        jobTypeDescription: jobTypeDescription,
        hasOutputFile: hasOutputFiles,
        outputCount: outputCount,
        arrayJobSupport: arrayJobSupport,
        hasFileUpload: hasFileUpload,
        arrayJobCount: arrayJobCount,
        organisationId: standalone ? selectedOrganisation : undefined,
      });
    }
  };

  const handleScriptChange = (value: string): void => {
    const updatedScript =
      scriptStart + "\n" + value.split("\n").slice(3).join("\n");

    setScript(updatedScript);
  };

  return (
    <div className="flex flex-col w-full items-center">
      <Card className="w-full max-w-2xl">
        <CardHeader>
          <CardTitle>Create a New Job Type</CardTitle>
          <CardDescription>
            Define a new Job Type by providing a name and bash script.
            Parameters that users will enter through the site should be written
            using {"{{parameterName}}"} format.{" "}
            {standalone
              ? "This JobType will be available to everyone in your organisation."
              : "This job type will be available to all users."}
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="jobTypeName">Job Name</Label>
            <Input
              id="jobTypeName"
              placeholder="Enter the Job Type Name"
              value={jobTypeName}
              className={`${isNameValid ? "" : "border-red-500"}`}
              onChange={(e) => setJobTypeName(e.target.value)}
            />
          </div>
          <div className="space-y-2 flex flex-col">
            <Label htmlFor="jobTypeDescription">Job Description</Label>

            <Label className="text-sm text-rose-500">
              Your description should indicate which files are which, i.e what
              file0 should be, and what file1 should be.
            </Label>

            <Textarea
              id="jobTypeDescription"
              placeholder="Enter Job Type Description"
              value={jobTypeDescription}
              className={`${isDescriptionValid ? "" : "border-red-500"}`}
              onChange={(e) => setJobTypeDescription(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="script">Bash Script</Label>
            <br />
            <Label className="text-sm text-red-500">
              The application assumes all code entered here is trusted. It is
              your responsibility to upload safe code. The first 3 lines cannot
              be changed.
            </Label>
            <div>
              <Editor
                height="300px"
                theme="vs-dark"
                value={script}
                onChange={(value) => {
                  handleScriptChange(value ?? "");
                  setParameters((prev) =>
                    updateParameterList(prev, extractParams(value ?? ""))
                  );
                }}
              />
            </div>
          </div>

          <div>
            <div className="flex flex-row justify-evenly">
              <div className="flex flex-col items-center">
                <Label htmlFor="fileUpload">Accepts File Uploads</Label>
                <Input
                  className="cursor-pointer bg-uol w-5"
                  id="fileUpload"
                  type="checkbox"
                  checked={hasFileUpload}
                  onChange={(e) => {
                    setHasFileUpload(e.target.checked);
                  }}
                />
              </div>

              <div className="flex flex-col items-center">
                <Label htmlFor="fileUpload">Outputs Files?</Label>
                <Input
                  className="cursor-pointer bg-uol w-5"
                  id="fileUpload"
                  type="checkbox"
                  checked={hasOutputFiles}
                  onChange={(e) => {
                    setHasOutputFiles(e.target.checked);
                    if (!e.target.checked) setOutputCount(0);
                    else setOutputCount(1);
                  }}
                />
              </div>
              <div className="flex flex-col items-center justify-center">
                <Label htmlFor="arrayJobSupport">Array Job?</Label>
                <Input
                  className="cursor-pointer bg-uol w-5"
                  id="arrayJobSupport"
                  type="checkbox"
                  checked={arrayJobSupport}
                  onChange={(e) => {
                    setArrayJobSupport(e.target.checked);
                  }}
                />
              </div>
            </div>

            {hasFileUpload && arrayJobSupport && (
              <div className="flex flex-col items-center justify-center">
                <Label htmlFor="arrayJobCount">How many files per job?</Label>
                <Input
                  id="arrayJobCount"
                  type="number"
                  onChange={(e) => {
                    setArrayJobCount(Number(e.target.value));
                  }}
                  value={arrayJobCount}
                />
              </div>
            )}

            <div className="flex flex-col w-full justify-evenly gap-2 p-2">
              {hasFileUpload && (
                <div className="flex flex-col">
                  <Label className="text-red-500 text-sm">
                    When referring to files in your script, please use bash
                    variables $file0, $file1, etc, unless being used in an array
                    job, in which case use "$arrayfile"
                  </Label>
                </div>
              )}
              {hasOutputFiles && (
                <div className="flex flex-col">
                  <Label htmlFor="fileCount">
                    How many files will be output?
                  </Label>
                  <Label className="text-red-500 text-sm">
                    When referring to files in your script, please use bash
                    variables out0, out1, etc.
                  </Label>
                  <Input
                    className="outputCount"
                    type="number"
                    value={outputCount}
                    onChange={(e) => {
                      setOutputCount(Number(e.target.value));
                    }}
                  />
                </div>
              )}
              {arrayJobSupport && (
                <Label className="text-red-500 text-sm">
                  This job is marked as an array job. Uploaded files will be
                  treated as the input for each job in the array. The script
                  should refer to the file as $arrayfile. If multiple files are
                  required for each job, $arrayfile0, $arrayfile1 etc, should be
                  used. A single config file can also be uploaded.
                </Label>
              )}
            </div>

            {parameters && parameters.length !== 0 && (
              <>
                <Label htmlFor="parameters">Parameters</Label>
                <div className="flex flex-row w-full justify-evenly mb-2 border-b-2">
                  <div className="w-1/4">
                    <label className="text-sm font-medium w-1/3">Name</label>
                  </div>
                  <div className="w-1/4">
                    <label className="text-sm font-medium w-1/3">Type</label>
                  </div>
                  <div className="w-1/4">
                    <label className="text-sm font-medium w-1/3">Default</label>
                  </div>
                </div>
                {parameters.map((param, index) => (
                  <div className="p-1">
                    <ParameterEntry
                      key={`${param.name}-${param.type}`}
                      parameters={parameters}
                      index={index}
                      setParameters={setParameters}
                      invalidParams={invalidParams}
                    />
                  </div>
                ))}
              </>
            )}

            {standalone && formattedOrganisations.length !== 0 && (
              <div className="w-full flex flex-col justify-center items-center gap-2">
                <Label>Select an Organisation for this JobType</Label>
                <Combobox
                  items={formattedOrganisations}
                  value={selectedOrganisation}
                  setValue={setSelectedOrganisation}
                  itemTypeName="Organisation"
                />
              </div>
            )}
          </div>
        </CardContent>
        <CardFooter className="justify-center p-4">
          <Button
            disabled={
              jobTypeName === "" ||
              jobTypeDescription === "" ||
              (standalone && selectedOrganisation === "")
            }
            className="bg-transparent border border-uol text-uol hover:bg-uol hover:text-white"
            onClick={() => createJob()}
          >
            Create Job Type
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
};

const CreateJobType = ({ standalone }: props): JSX.Element => {
  return standalone ? (
    <div className="flex flex-col w-full min-h-screen">
      <Nav />
      <div className="p-2">
        <CreateJobTypeInterface standalone />
      </div>
    </div>
  ) : (
    <CreateJobTypeInterface />
  );
};

export default CreateJobType;
