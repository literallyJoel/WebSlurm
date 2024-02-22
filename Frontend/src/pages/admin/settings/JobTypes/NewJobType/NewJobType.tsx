import { useContext, useEffect, useState } from "react";
import {
  type Parameter,
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
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";

const NewJobType = (): JSX.Element => {
  const scriptStart =
    "#!/bin/bash\n#SBATCH --job-name=*{name}*\n#SBATCH --output=*{out}*";
  const [script, setScript] = useState(scriptStart);
  const [parameters, setParameters] = useState<Parameter[]>([]);
  const [invalidParams, setInvalidParams] = useState<number[]>([]);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [hasFileUpload, setHasFileUpload] = useState(false);
  const [isDescriptionValid, setIsDescriptionValid] = useState(true);
  const [hasImageUpload, setHasImageUpload] = useState(false);
  const [fileCount, setFileCount] = useState(0);
  const [imageCount, setImageCount] = useState(0);
  const [isNameValid, setIsNameValid] = useState(true);
  const { getToken } = useContext(AuthContext);
  const token = getToken();
  const createJobTypeRequest = useMutation(
    "createJobType",
    (jobType: JobTypeCreation) => {
      return createJobType(jobType);
    },
    {
      onSuccess: () => {
        window.location.href = "/admin/jobtypes";
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
        fileUploadCount: fileCount,
        imgUploadCount: imageCount,
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
            {(fileCount !== 0 || imageCount !== 0) && (
              <Label className="text-sm text-rose-500">
                Your description should indicate which files are which, i.e what
                file0 should be, and what file1 should be.
              </Label>
            )}
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
            <div className="flex flex-row w-full justify-evenly">
              <div>
                <Label htmlFor="fileUpload">
                  Accepts File Uploads (PDF, DOCX, etc.)
                </Label>
                <Input
                  className="cursor-pointer bg-uol"
                  id="fileUpload"
                  type="checkbox"
                  checked={hasFileUpload}
                  onChange={(e) => {
                    setHasFileUpload(e.target.checked);
                    if (!e.target.checked) setFileCount(0);
                  }}
                />
              </div>
              <div className="self-end">
                <Label htmlFor="imageUpload">Accepts Image Uploads</Label>
                <Input
                  className="cursor-pointer"
                  id="imageUpload"
                  type="checkbox"
                  checked={hasImageUpload}
                  onChange={(e) => {
                    setHasImageUpload(e.target.checked);
                    if (!e.target.checked) setImageCount(0);
                  }}
                />
              </div>
            </div>

            <div className="flex flex-row w-full justify-evenly gap-2 p-2">
              {hasFileUpload && (
                <div className="flex flex-col w-1/2">
                  <Label htmlFor="fileCount">
                    How many files will be uploaded?
                  </Label>
                  <Label className="text-red-500 text-sm">
                    When referring to files in your script, please use bash
                    variables file0, file1, etc.
                  </Label>
                  <Input
                    className="fileCount"
                    type="number"
                    value={fileCount}
                    onChange={(e) => {
                      setFileCount(Number(e.target.value));
                    }}
                  />
                </div>
              )}

              {hasImageUpload && (
                <div className="flex flex-col w-1/2">
                  <Label htmlFor="fileCount">
                    How many images will be uploaded?
                  </Label>
                  <Label className="text-red-500 text-sm">
                    When referring to images in your script, please use bash
                    variables img0, img1, etc.
                  </Label>
                  <Input
                    className="imageCount"
                    type="number"
                    value={imageCount}
                    onChange={(e) => {
                      setImageCount(Number(e.target.value));
                    }}
                  />
                </div>
              )}
            </div>

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

export default NewJobType;
