import { useEffect, useRef, useState } from "react";
import {
  type JobTypeParameter,
  extractParams,
  updateParamaterList,
  JobTypeCreation,
  createJobType,
} from "../../../../../helpers/jobTypes";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { Textarea } from "@/shadui/ui/textarea";
import { Label } from "@radix-ui/react-label";
import { Input } from "@/shadui/ui/input";
import { Editor } from "@monaco-editor/react";
import ParameterEntry from "../components/ParameterEntry";
import { Button } from "@/shadui/ui/button";
import { validateParameters } from "@/helpers/validation";
import { useMutation } from "react-query";
import { Link } from "react-router-dom";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";

const CreateJobType = (): JSX.Element => {
  const scriptStart =
    "#!/bin/bash\n#SBATCH --job-name=*{name}*\n#SBATCH --output=*{out}*";
  const [script, setScript] = useState(scriptStart);
  const [parameters, setParameters] = useState<JobTypeParameter[]>([]);
  const [invalidParams, setInvalidParams] = useState<number[]>([]);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [hasFileUpload, setHasFileUpload] = useState(false);
  const [hasOutputFiles, setHasOutputFiles] = useState(false);
  const [outputCount, setOutputCount] = useState(0);
  const [isDescriptionValid, setIsDescriptionValid] = useState(true);
  const [isNameValid, setIsNameValid] = useState(true);
  const [arrayJobSupport, setArrayJobSupport] = useState(false);
  const [arrayJobCount, setArrayJobCount] = useState(0);
  const authContext = useAuthContext();
  const hiddenRef = useRef<HTMLAnchorElement>(null);
  const token = authContext.getToken();

  const createJobTypeRequest = useMutation(
    "createJobType",
    (jobType: JobTypeCreation) => {
      return createJobType(jobType);
    },
    {
      onSuccess: () => {
        hiddenRef.current?.click();
      },
    }
  );

  const createJob = (): void => {
    const _invalidParams = validateParameters(parameters);
    setInvalidParams(_invalidParams);

    setIsNameValid(name !== "");
    setIsDescriptionValid(description !== "");
    if (_invalidParams.length === 0 && name !== "" && description !== "") {
      createJobTypeRequest.mutate({
        name: name,
        script: script,
        parameters: parameters,
        description: description,
        token: token,
        hasOutputFile: hasOutputFiles,
        outputCount: outputCount,
        arrayJobSupport: arrayJobSupport,
        hasFileUpload: hasFileUpload,
        arrayJobCount: arrayJobCount,
      });
    }
  };

  const handleScriptChange = (value: string): void => {
    const updatedScript =
      scriptStart + "\n" + value.split("\n").slice(3).join("\n");

    setScript(updatedScript);
  };

  useEffect(() => {
    if (!arrayJobSupport || !hasFileUpload) {
      setArrayJobCount(0);
    } else if (arrayJobSupport && hasFileUpload) {
      if (arrayJobCount === 0) {
        setArrayJobCount(1);
      }
    }
  }, [arrayJobSupport, hasFileUpload]);
  return (
    <div className="flex flex-col w-full items-center">
      <Card className="w-full max-w-2xl">
        <Link to="/admin/jobtypes" className="hidden" ref={hiddenRef} />
        <CardHeader>
          <CardTitle>Create a New Job Type</CardTitle>
          <CardDescription>
            Define a new Job Type by providing a name and bash script.
            Parameters that users will enter through the site should be written
            using {"{{parameterName}}"} format.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="jobName">Job Name</Label>
            <Input
              id="jobName"
              placeholder="Enter the Job Type Name"
              value={name}
              className={`${isNameValid ? "" : "border-red-500"}`}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          <div className="space-y-2 flex flex-col">
            <Label htmlFor="jobDescription">Job Description</Label>

            <Label className="text-sm text-rose-500">
              Your description should indicate which files are which, i.e what
              file0 should be, and what file1 should be.
            </Label>

            <Textarea
              id="jobDescription"
              placeholder="Enter Job Type Description"
              value={description}
              className={`${isDescriptionValid ? "" : "border-red-500"}`}
              onChange={(e) => setDescription(e.target.value)}
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
                    updateParamaterList(prev, extractParams(value ?? ""))
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
          </div>
        </CardContent>
        <CardFooter className="justify-center p-4">
          <Button
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

export default CreateJobType;
