"use client";

import {
  ChevronRight,
  LayoutDashboardIcon,
  type LucideIcon,
} from "lucide-react";

import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar";
import Link from "next/link";
import { usePathname } from "next/navigation";

export function NavMain({
  items,
}: {
  items: {
    title: string;
    url: string;
    icon?: LucideIcon;
    isActive?: boolean;
    isSingle?: boolean;
    items?: {
      title: string;
      url: string;
    }[];
  }[];
}) {
  const pathname = usePathname();

  function normalizePath(path: string) {
    if (!path) return "/";
    return path.endsWith("/") && path !== "/" ? path.slice(0, -1) : path;
  }

  return (
    <SidebarGroup>
      <SidebarGroupLabel></SidebarGroupLabel>
      <SidebarMenu>
        {items
          .filter((item) => item.isSingle)
          .map((item) => {
            const active = normalizePath(pathname) === normalizePath(item.url);
            return (
              <SidebarMenuItem key={item.title}>
                <SidebarMenuButton asChild>
                  <Link href={item.url}>
                    {item.icon ? <item.icon /> : <LayoutDashboardIcon />}
                    <span
                      className={active ? "font-bold text-primary" : undefined}>
                      {item.title}
                    </span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            );
          })}

        {items
          .filter((item) => !item.isSingle)
          .map((item) => {
            const hasActiveChild = item.items?.some(
              (subItem) => pathname === subItem.url
            );

            return (
              <Collapsible
                key={item.title}
                asChild
                defaultOpen={hasActiveChild}
                className="group/collapsible">
                <SidebarMenuItem>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={item.title}>
                      {item.icon && <item.icon />}
                      <span
                        className={
                          hasActiveChild ? "font-bold text-primary" : undefined
                        }>
                        {item.title}
                      </span>
                      <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                  </CollapsibleTrigger>
                  <CollapsibleContent>
                    <SidebarMenuSub>
                      {item.items?.map((subItem, idx) => {
                        if (!subItem?.url) return null;
                        const active =
                          normalizePath(pathname) ===
                          normalizePath(subItem.url);
                        return (
                          <SidebarMenuSubItem key={`${item.title}-${idx}`}>
                            <SidebarMenuSubButton asChild>
                              <Link href={subItem.url}>
                                <span
                                  className={
                                    active
                                      ? "font-bold text-primary"
                                      : undefined
                                  }>
                                  {subItem.title}
                                </span>
                              </Link>
                            </SidebarMenuSubButton>
                          </SidebarMenuSubItem>
                        );
                      })}
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </SidebarMenuItem>
              </Collapsible>
            );
          })}
      </SidebarMenu>
    </SidebarGroup>
  );
}
