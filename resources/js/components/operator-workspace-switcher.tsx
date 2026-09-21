import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';

type Operator = { id:number; name:string; uasoc_number?:string|null };
type Workspace = { active_operator:Operator|null; operators:Operator[]; requires_selection:boolean; can_manage:boolean };

export function OperatorWorkspaceSwitcher() {
 const { operatorWorkspace } = usePage<{operatorWorkspace:Workspace|null}>().props;
 const [open,setOpen]=useState(false);
 if (!operatorWorkspace || operatorWorkspace.operators.length===0) return null;
 const active=operatorWorkspace.active_operator;
 const select=(id:number)=>{ setOpen(false); router.post('/operator-workspace',{operator_id:id},{preserveScroll:true}); };
 return <div className="relative px-2 pb-2">
  <button type="button" onClick={()=>setOpen(!open)} className="flex w-full items-center gap-2 rounded-lg border bg-background px-3 py-2 text-left text-sm hover:bg-muted">
   <Building2 className="size-4 shrink-0"/><span className="min-w-0 flex-1"><span className="block truncate font-medium">{active?.name ?? 'Select operator'}</span><span className="block truncate text-xs text-muted-foreground">{active?.uasoc_number ?? (operatorWorkspace.requires_selection?'Workspace required':'Operator workspace')}</span></span><ChevronsUpDown className="size-4"/>
  </button>
  {open && <div className="absolute left-2 right-2 z-50 mt-1 rounded-lg border bg-popover p-1 shadow-lg">
   {operatorWorkspace.operators.map(op=><button key={op.id} type="button" onClick={()=>select(op.id)} className="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-muted"><span className="flex-1 truncate">{op.name}</span>{active?.id===op.id&&<Check className="size-4"/>}</button>)}
  </div>}
 </div>;
}