import ownet
import rrdtool
from owtg import *
from time import localtime, mktime

ownet.init('localhost:4304')

dAddresses = [s.address for s in getSensors()] #already discovered addresses
gAddresses = [s.address for s in getSensors() if s.graph == True] #Addresses with "graph" turned on
newAddresses = [] #new addresses found during this run
newFile = [] #array of lines to write out to file

newFile.append('#This is an automatically generated file\n')
newFile.append('#DO NOT edit this file by hand; it may cause undefined behaviour\n')
newFile.append('#[alias]:[address]:[timestamp]:[graph(y/n)]\n')

sFile = open(sFilename,'r') #discovered file, open for reading
lineList = sFile.readlines()
sFile.close()

for line in lineList:
    if line.startswith('#'):
        continue
    newFile.append(line)

for directory in ownet.Sensor('/','localhost',4304).sensorList():
    seen = False
    address = None
    #exclude "simultaneous" as it has a temperature file but is not a sensor
    if directory == ownet.Sensor('/simultaneous','localhost',4304):
        continue

    #If the directory contains "temperature", it is a sensor
    if hasattr(directory,'temperature'):
        #store the address
        address = directory.address
        for a in dAddresses:
            #check against every discovered addresss
            if address == a:
                seen = True
                break
        #if the address has not been already discovered, add it to newAddresses
        if not seen:
            newAddresses.append(address)

for a in newAddresses:
    #Build a string in the form of "[alias(empty)]:[address]:[timestamp]:[graph(y/n)]:[min-alarm]:[max-alarm]\n"
    sensorLine = ':' + a + ':' + str(int(mktime(localtime()))) + ':n:20:30\n'
    #Append it to the new file line array
    newFile.append(sensorLine)
    
for a in gAddresses:
    claimed = False #Has it been claimed in the RRD?
    foundUnclaimed = False #Has an unclaimed DS been found?
    firstUnclaimed = '-1' #ID of the first seen unclaimed DS
    for dsName in rrdtool.fetch(adbFilename, 'AVERAGE')[1]:
        if dsName == a:
            claimed = True
        if dsName.split('_')[0] == 'unclaimed' and not foundUnclaimed:
            foundUnclaimed = True
            firstUnclaimed = dsName.split('_')[1]
    if not claimed:
        rrdtool.tune(adbFilename, '--data-source-rename', 'unclaimed_'+firstUnclaimed+':'+a)
        rrdtool.tune(gdbFilename, '--data-source-rename', 'unclaimed_'+firstUnclaimed+':'+a)

newFile.sort()
sFile = open(sFilename,'w') #sensors file, open for writing
sFile.writelines(newFile)
sFile.close()

