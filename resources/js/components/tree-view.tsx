import { router } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Loader2, MoreHorizontal } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

export type TreeAction = {
    label: string;
    href?: string;
    onClick?: () => void;
    variant?: 'default' | 'destructive';
};

export type TreeNode = {
    id: number | string;
    label: string;
    badge?: string;
    isActive?: boolean;
    icon?: LucideIcon;
    actions?: TreeAction[];
    children?: TreeNode[];
    hasChildren?: boolean;
    fetchChildrenUrl?: string;
};

type TreeNodeItemProps = {
    node: TreeNode;
    depth: number;
    onFetchChildren?: (node: TreeNode) => Promise<TreeNode[]>;
};

function TreeNodeItem({ node, depth, onFetchChildren }: TreeNodeItemProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [children, setChildren] = useState<TreeNode[]>(node.children ?? []);
    const [loading, setLoading] = useState(false);

    const canExpand = node.hasChildren || (node.children && node.children.length > 0);

    const handleToggle = useCallback(async () => {
        if (!canExpand) return;

        if (!isOpen && node.hasChildren && children.length === 0 && onFetchChildren) {
            setLoading(true);
            try {
                const fetched = await onFetchChildren(node);
                setChildren(fetched);
            } finally {
                setLoading(false);
            }
        }

        setIsOpen((prev) => !prev);
    }, [isOpen, node, children.length, canExpand, onFetchChildren]);

    const Icon = node.icon;

    return (
        <div>
            <div
                className={cn(
                    'group flex items-center gap-1 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent',
                    !node.isActive && 'opacity-60',
                )}
                style={{ paddingLeft: `${depth * 16 + 8}px` }}
            >
                {/* Expand/collapse toggle */}
                <button
                    type="button"
                    onClick={handleToggle}
                    className={cn(
                        'flex h-5 w-5 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:text-foreground',
                        !canExpand && 'invisible',
                    )}
                    aria-label={isOpen ? 'Colapsar' : 'Expandir'}
                >
                    {loading ? (
                        <Loader2 className="h-3.5 w-3.5 animate-spin" />
                    ) : isOpen ? (
                        <ChevronDown className="h-3.5 w-3.5" />
                    ) : (
                        <ChevronRight className="h-3.5 w-3.5" />
                    )}
                </button>

                {/* Icon */}
                {Icon && <Icon className="h-4 w-4 shrink-0 text-muted-foreground" />}

                {/* Label */}
                <span className="flex-1 truncate font-medium">{node.label}</span>

                {/* Badge */}
                {node.badge && (
                    <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                        {node.badge}
                    </span>
                )}

                {/* Actions */}
                {node.actions && node.actions.length > 0 && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-6 w-6 shrink-0 opacity-0 transition-opacity group-hover:opacity-100"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <MoreHorizontal className="h-3.5 w-3.5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {node.actions.map((action) => (
                                <DropdownMenuItem
                                    key={action.label}
                                    className={cn(
                                        action.variant === 'destructive' && 'text-destructive',
                                    )}
                                    onClick={() => {
                                        if (action.href) {
                                            router.visit(action.href);
                                        } else if (action.onClick) {
                                            action.onClick();
                                        }
                                    }}
                                >
                                    {action.label}
                                </DropdownMenuItem>
                            ))}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>

            {/* Children */}
            {isOpen && children.length > 0 && (
                <div>
                    {children.map((child) => (
                        <TreeNodeItem
                            key={child.id}
                            node={child}
                            depth={depth + 1}
                            onFetchChildren={onFetchChildren}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

type TreeViewProps = {
    nodes: TreeNode[];
    onFetchChildren?: (node: TreeNode) => Promise<TreeNode[]>;
    className?: string;
};

export function TreeView({ nodes, onFetchChildren, className }: TreeViewProps) {
    return (
        <div className={cn('select-none', className)}>
            {nodes.map((node) => (
                <TreeNodeItem
                    key={node.id}
                    node={node}
                    depth={0}
                    onFetchChildren={onFetchChildren}
                />
            ))}
        </div>
    );
}
