"use client";
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuPortal,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Icons } from "@/components/icons";
import {
  Drawer,
  DrawerContent,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
  DrawerTrigger,
} from "@/components/ui/drawer";
import { siteConfig } from "@/lib/config";
import { cn } from "@/lib/utils";
import Link from "next/link";
import { IoMenuSharp } from "react-icons/io5";
import { useUser } from "@/providers/user-context";
import { VisuallyHidden } from "@radix-ui/react-visually-hidden";
import { Separator } from "./ui/separator";
import { UserInfo } from "./sections/user-info";

export default function DrawerComponent() {
  const {
    userInfo,
    doLogin,
    isShowLogin,
    setIsShowLogin,
    isShowRegister,
    setIsShowRegister,
    isShowLostPassword,
    setIsShowLostPassword,
  } = useUser();

  return (
    <Drawer>
      <DrawerTrigger>
        <IoMenuSharp className="text-2xl" />
      </DrawerTrigger>
      <DrawerContent>
        <VisuallyHidden>
          <DrawerTitle></DrawerTitle>
        </VisuallyHidden>
        <DrawerHeader className="px-6">
          <div>
            <Link
              href="/"
              title="brand-logo"
              className="relative mr-6 flex items-center space-x-2"
            >
              <Icons.logoThienDieu className="w-[200px]" />
              {/* <span className="font-bold text-xl">{siteConfig.name}</span> */}
            </Link>
          </div>
          <nav>
            <ul className="mt-7 text-left">
              {siteConfig.header.map((item, index) => (
                <li key={index} className="my-3">
                  {item.trigger ? (
                    <span className="font-semibold">{item.trigger}</span>
                  ) : (
                    <Link href={item.href || ""} className="font-semibold">
                      {item.label}
                    </Link>
                  )}
                </li>
              ))}
            </ul>
          </nav>
        </DrawerHeader>
        <DrawerFooter className="items-center justify-center">
          <Separator className="mb-4" />
          {userInfo ? (
            <UserInfo user={userInfo} />
          ) : (
            <div className="flex gap-2">
              <Button
                variant="outline"
                onClick={() => {
                  setIsShowLogin(true);
                }}
              >
                Đăng nhập
              </Button>
              <Button
                onClick={() => {
                  setIsShowRegister(true);
                }}
                className="w-auto text-background flex gap-2"
              >
                <Icons.logoThienDieuIcon /> Đăng ký học viên
              </Button>
            </div>
          )}
        </DrawerFooter>
      </DrawerContent>
    </Drawer>
  );
}
