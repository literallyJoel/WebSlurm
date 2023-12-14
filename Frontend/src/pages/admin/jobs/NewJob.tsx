import { useState } from "react";
import {
  CardTitle,
  CardDescription,
  CardHeader,
  CardContent,
  CardFooter,
  Card,
} from "@/shadui/ui/card";
import { Label } from "@/shadui/ui/label";
import { Input } from "@/shadui/ui/input";
import { Button } from "@/shadui/ui/button";
import { Editor } from "@monaco-editor/react";
import ParameterModal from "./components/ParameterModal";

const NewJob = (): JSX.Element => {
  const [code, setCode] = useState("");
  const [isModalOpen, setIsModalOpen] = useState(false);

  const openModal = () => {
    setIsModalOpen(true);
  };

  const closeModal = () => {
    setIsModalOpen(false);
  };

  const handleAddParameter = (parameterName: string, parameterType: string) => {
    // Handle adding the parameter logic here
    console.log(
      `Parameter Name: ${parameterName}, Parameter Type: ${parameterType}`
    );
    closeModal(); // Close the modal after handling the parameter
  };

  return (
    <div className="flex flex-col w-full items-center">
      <Card className="w-full max-w-2xl">
        <CardHeader>
          <CardTitle>Create New Job Type</CardTitle>
          <CardDescription>
            Define the job specification including the bash script
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="job-name">Job Name</Label>
            <Input id="job-name" placeholder="Enter the job name" />
          </div>
          <div className="space-y-2">
            <Label htmlFor="bash-code">Bash Script</Label> <br/>
            <Label className="text-sm text-red-500">
              The application will assume this code is trusted. It is your
              responsibility to upload a safe script.
            </Label>
            <Editor
              height="300px"
              language="bash"
              theme="vs-dark"
              value={code}
              onChange={(value) => setCode(value ?? "")}
            />
          </div>
        </CardContent>
        <CardFooter className="justify-between">
          <Button onClick={openModal}>Add Parameter</Button>
          <Button className="bg-emerald-600 hover:bg-emerald-800">
            Create Job Type
          </Button>
        </CardFooter>
      </Card>

      {/* Render the modal component when the state is true */}
      {isModalOpen && (
        <ParameterModal
          onClose={closeModal}
          onAddParameter={handleAddParameter}
        />
      )}
    </div>
  );
};

export default NewJob;
