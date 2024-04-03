import Nav from "@/components/Nav";
import { Button } from "@/components/shadui/ui/button";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { useEffect, useState } from "react";
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
            <CardTitle className="w-full text-center p-2">
              Account Created
            </CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col items-center gap-4">
            <FaRegCheckCircle className="text-green-500" size={60} />
            {generatedPass && (
              <div>A temporary password has been sent to the user</div>
            )}
          </CardContent>
          <CardFooter className="justify-center">
            <Link to="/">
              <Button className="border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner">
                Back to home
              </Button>
            </Link>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};
