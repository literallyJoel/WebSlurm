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
import Nav from "@/pages/components/Nav";
import { useContext, useState } from "react";
import { AuthContext } from "@/providers/auth/AuthProvider";
import { MdEdit } from "react-icons/md";

export default function AccountPanel() {
  const { getUser } = useContext(AuthContext);
  const user = getUser();
  const [isEditingName, setIsEditingName] = useState(false);
  const [name, setName] = useState(user.name);
  const [isEditingEmail, setIsEditingEmail] = useState(false);
  const [email, setEmail] = useState(user.email);
  const [isEditingPassword, setIsEditingPassword] = useState(false);
  const [password, setPassword] = useState("");
  const [confirmPass, setConfirmPass] = useState("");

  const NameEntry = () => {
    if (isEditingName) {
      return (
        <div className="flex flex-col gap-2">
          <Label htmlFor="name">Name</Label>

          <Input
            id="name"
            placeholder="name"
            value={name}
            onChange={(e) => setName(e.target.value)}
          />
        </div>
      );
    }

    return (
      <div className="flex flex-col gap-2">
        <div className="flex flex-row gap-2">
          <Label htmlFor="name">Name</Label>
          <MdEdit className="cursor-pointer" onClick={() => setIsEditingName(true)} />
        </div>

        <span id="name">{name}</span>
      </div>
    );
  };

  const EmailEntry = () => {
    if (isEditingEmail) {
      return (
        <div className="flex flex-col gap-2">
          <Label htmlFor="email">Email</Label>
          <Input
            type="email"
            id="email"
            placeholder="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
        </div>
      );
    }

    return (
      <div className="flex flex-col gap-2">
        <div className="flex flex-row gap-2">
          <Label htmlFor="email">Email</Label>
          <MdEdit className="cursor-pointer" onClick={() => setIsEditingEmail(true)}/>
        </div>

        <span id="email">{email}</span>
      </div>
    );
  };

  const PasswordEntry = () => {
    if (isEditingPassword) {
      return (
        <div className="flex flex-col gap-2">
          <Label htmlFor="password">Password</Label>
          <Input
            id="password"
            placeholder="Password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
          <Label htmlFor="confirmPassword">Confirm Password</Label>
          <Input
            id="confirmPassword"
            placeholder="Confirm Password"
            value={confirmPass}
            onChange={(e) => setConfirmPass(e.target.value)}
          />
        </div>
      );
    }
  };
  return (
    <>
      <Nav />
      <Card className="w-full max-w-md mx-auto mt-4">
        <CardHeader>
          <CardTitle>Your Account</CardTitle>
          <CardDescription>Manage your account here</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <NameEntry />
          </div>
          <div className="space-y-2">
            <EmailEntry />
          </div>

          <div className="space-y-2">
            <PasswordEntry />
          </div>
          <div className={`${isEditingPassword ? "hidden" : "space-y-2"}`}>
            <Button
              className="ml-auto"
              onClick={() => setIsEditingPassword(true)}
            >
              Update Password
            </Button>
          </div>
        </CardContent>
        <CardFooter>
          <Button className="w-full">Save Changes</Button>
        </CardFooter>
        <CardContent className="space-y-4">
          <Button
            className="w-full text-red-500 border-red-500"
            variant="outline"
          >
            Delete Account
          </Button>
          <Button className="w-full" variant="outline">
            Sign Out Everywhere
          </Button>
        </CardContent>
      </Card>
    </>
  );
}
