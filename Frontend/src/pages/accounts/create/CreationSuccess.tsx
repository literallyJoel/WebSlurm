import Nav from "@/components/Nav";
import { Button } from "@/shadui/ui/button";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { useEffect, useState } from "react";
import { CiMedicalClipboard } from "react-icons/ci";
import { FaRegCheckCircle } from "react-icons/fa";
import { Link } from "react-router-dom";

export const CreationSuccess = (): JSX.Element => {
  const [copyStatus, setCopyStatus] = useState(0);
  const [generatedPass, setGeneratedPass] = useState<string | undefined>();

  useEffect(() => {
    setTimeout(() => {
      setCopyStatus(0);
    }, 5000);
  }, [copyStatus]);

  useEffect(() => {
    setGeneratedPass(localStorage.getItem("gpass") ?? undefined);
    localStorage.removeItem("gpass");
  }, []);

  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 mb-10 flex-grow">
        <Card className="max-w-2xl mx-auto">
          <CardHeader>
            <CardTitle>Account Created</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col items-center gap-4">
            <FaRegCheckCircle className="text-green-500" size={60} />

            {generatedPass && (
              <>
                <span>Their temporary password is </span>
                <div
                  className="border border-black rounded-xl w-5/12 p-2 flex flex-row items-center text-center hover:bg-slate-200 cursor-pointer"
                  onClick={() =>
                    navigator.clipboard
                      .writeText(generatedPass)
                      .then(() => setCopyStatus(1))
                      .catch(() => setCopyStatus(-1))
                  }
                >
                  <div className="w-2/12">
                    <CiMedicalClipboard size={20} />
                  </div>
                  <div className="w-8/12">{generatedPass}</div>
                </div>
                <div
                  className={`${
                    copyStatus === 1 ? "absolute" : "hidden"
                  } -right-4 text-xs  bg-black bg-opacity-40 rounded-xl backdrop-blur-md text-white p-3`}
                >
                  Successfully copied to clipboard
                </div>
                <span className="text-center">
                  This password will not be shown again. <br />
                  The user will be required to change their password on their
                  next sign in attempt.
                </span>
              </>
            )}
          </CardContent>
          <CardFooter className="justify-center">
            <Link to="/">
              <Button className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner">
                Back to home
              </Button>
            </Link>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};
