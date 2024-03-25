import { useMutation, useQuery } from "react-query";
import { User, getAllAccounts } from "@/helpers/accounts";
import { useContext, useState } from "react";
// import { validateName } from "@/helpers/validation";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { Label } from "@/shadui/ui/label";
import { Input } from "@/shadui/ui/input";
import { Select, SelectContent, SelectItem } from "@/shadui/ui/select";
import { Button } from "@/shadui/ui/button";
import { SelectTrigger, SelectValue } from "@radix-ui/react-select";
import { NewOrganisation, createOrganisation } from "@/helpers/organisations";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { Badge } from "@/shadui/ui/badge";
import { CreationSuccess } from "./components/CreationSuccess";
import { CreationFailure } from "./components/CreationFailure";

const CreateOrganisation = (): JSX.Element => {
  const [organisationName, setOrganisationName] = useState("");
  const [ownerId, setOwnerId] = useState("");
  const [isOrganisationNameValid, setIsOrganisationNameValid] = useState(true);
  const [selectedUser, setSelectedUser] = useState<User>();
  const [isOwnerValid, setIsOwnerValid] = useState(true);
  const [newOrgId, setNewOrgId] = useState("");
  const [view, setView] = useState<"create" | "success" | "failure">("create");
  const token = useContext(AuthContext).getToken();
  const currentUser = useContext(AuthContext).getUser();
  const _createOrganisation = (): void => {
    if (isOrganisationNameValid && isOwnerValid) {
      callCreateOrg.mutate(
        { organisationName: organisationName, ownerId: ownerId },
        {
          onSuccess: (data) => {
            setNewOrgId(data.orgId);
            setView("success");
          },
          onError: () => {
            setView("failure");
            //!Temp!
            setIsOwnerValid(false);
            setIsOrganisationNameValid(false);
          },
        }
      );
    }
  };

  const { data: allUsers } = useQuery(
    "allUsers",
    () => {
      return getAllAccounts(token);
    },
    {
      onSuccess: (data) => {
        setSelectedUser(data[0]);
      },
    }
  );

  const callCreateOrg = useMutation(
    "createOrganisation",
    (organisation: NewOrganisation) => {
      return createOrganisation(
        organisation.organisationName,
        organisation.ownerId,
        token
      );
    }
  );

  if (view === "create") {
    return (
      <div className="">
        <Card className="max-w-2xl mx-auto">
          <CardHeader>
            <CardTitle>Create a new Organisation</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="flex flex-col gap-4">
                <div className="space-y-2 flex flex-col">
                  <Label htmlFor="nameInput">Organisation Name</Label>
                  <Label
                    className={`text-xs text-red-500 ${
                      isOrganisationNameValid ? "hidden" : ""
                    }`}
                  >
                    Please enter an organisation name.
                  </Label>
                  <Input
                    id="nameInput"
                    className={`mt-2 text-center${
                      isOrganisationNameValid ? "" : "border-red-500"
                    }`}
                    placeholder="University of Liverpool"
                    value={organisationName}
                    onChange={(e) => setOrganisationName(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2 flex flex-col">
                  <Label htmlFor="email">Organisation Owner</Label>
                  <Label className="text-xs text-red-500">
                    The selected user will have full control over the
                    organisation, including the ability to delete it.
                  </Label>
                  <Label
                    htmlFor="email"
                    className={`text-xs text-red-500 ${
                      isOwnerValid ? "hidden" : ""
                    }`}
                  >
                    Please select an owner for this organisation.
                  </Label>
                  <Select
                    required
                    value={selectedUser?.userID}
                    onValueChange={(e) => {
                      setSelectedUser(() => {
                        const _filtered = allUsers?.find(
                          (user) => user.userID === e
                        );

                        if (_filtered) {
                          setOwnerId(_filtered.userID);
                          return _filtered;
                        }
                        return;
                      });
                    }}
                  >
                    <SelectTrigger className="border border-[#E2E8F0] h-10 rounded-md relative">
                      <SelectValue placeholder="Select a user" />
                      {/* Displays a dropdown arrow. */}
                      <div className="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <svg
                          className="h-4 w-4 fill-current text-gray-500"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                        >
                          <path d="M10 12l-6-6-1.414 1.414L10 14.828l7.414-7.414L16 6z"></path>
                        </svg>
                      </div>
                    </SelectTrigger>
                    <SelectContent>
                      {allUsers?.map((user) => (
                        <SelectItem key={user.userID} value={user.userID}>
                          <div className="flex flex-row gap-1 justify-between p-2">
                            <div>{user.userName}&nbsp;</div>
                            <div className="flex flex-row gap-1 mr-6">
                              {Number(user.role) === 1 && (
                                <Badge className="bg-emerald-500 rounded-md w-12 text-xs justify-center">
                                  Admin
                                </Badge>
                              )}
                              {user.userID === currentUser.id && (
                                <Badge className="bg-orange-500 rounded-md w-10 text-xs justify-center">
                                  You
                                </Badge>
                              )}
                              <Badge className=" bg-slate-400 rounded-md text-slate-200 text-xs justify-center">
                                {user.userEmail.split("@")[1]}
                              </Badge>
                            </div>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>
          </CardContent>
          <CardFooter className="justify-center">
            <Button
              className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
              onClick={() => _createOrganisation()}
            >
              Create Organisation
            </Button>
          </CardFooter>
        </Card>
      </div>
    );
  } else if (view === "success") {
    return <CreationSuccess orgId={newOrgId} orgName={organisationName} />;
  } else {
    return <CreationFailure setView={setView} />;
  }
};

export default CreateOrganisation;
